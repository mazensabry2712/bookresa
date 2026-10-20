<?php

namespace App\Domain\Payment\Services;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierWebhookVerifier;
use App\Support\PlatformSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class HandleKashierWebhook
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly KashierWebhookVerifier $verifier,
        private readonly PaymentService $payments,
        private readonly SyncBookingPaymentStatus $bookingPaymentSync,
        private readonly SyncSubscriptionPaymentStatus $subscriptionPaymentSync,
        private readonly PlatformSettings $platformSettings,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload, string $signature): bool
    {
        $data = $payload['data'] ?? null;

        if (!is_array($data)) {
            throw new RuntimeException('Invalid Kashier webhook payload.');
        }

        $event = strtolower(trim((string) ($payload['event'] ?? '')));
        $transactionId = (string) ($data['transactionId'] ?? '');
        $orderReference = (string) ($data['orderReference'] ?? '');
        $merchantOrderId = (string) ($data['merchantOrderId'] ?? '');
        $status = strtoupper((string) ($data['status'] ?? ''));

        if ($event === '' || $transactionId === '' || $merchantOrderId === '' || $orderReference === '' || !in_array($status, ['SUCCESS', 'FAILURE', 'PENDING'], true)) {
            throw new RuntimeException('Kashier webhook payload is missing required fields.');
        }

        $kashierConfig = $this->platformSettings->kashierConfig();
        $apiKey = (string) ($kashierConfig['api_key'] ?? '');

        if (!$this->verifier->verify($data, $signature, $apiKey)) {
            throw new LogicException('Invalid Kashier webhook signature.');
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'kashier')
            ->where('reference', $merchantOrderId)
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Payment for Kashier merchant order ID was not found.');
        }

        $eventAmountMinor = $this->amountToMinor($data['amount'] ?? null);

        if ($event === 'partial_refund') {
            if ($eventAmountMinor <= 0 || $eventAmountMinor > (int) $payment->amount_minor) {
                throw new RuntimeException('Kashier partial refund amount is invalid.');
            }
        } elseif ($eventAmountMinor !== (int) $payment->amount_minor) {
            throw new RuntimeException('Kashier webhook amount does not match the payment.');
        }

        if (strtoupper((string) ($data['currency'] ?? '')) !== strtoupper((string) $payment->currency)) {
            throw new RuntimeException('Kashier webhook currency does not match the payment.');
        }

        $merchantId = trim((string) ($data['merchantId'] ?? ''));
        $usesCentralPlatformMerchant = $payment->payable_type === Subscription::class
            || filter_var(data_get($payment->metadata, 'centralized_payment', false), FILTER_VALIDATE_BOOL);
        $expectedMerchantId = $usesCentralPlatformMerchant
            ? (string) ($kashierConfig['merchant_id'] ?? '')
            : trim((string) data_get($payment->metadata, 'connected_account_merchant_id'));

        if ($merchantId === '' || $expectedMerchantId === '' || $merchantId !== $expectedMerchantId) {
            throw new RuntimeException('Kashier webhook merchant account does not match the payment.');
        }

        $tenant = $payment->tenant;

        if ($tenant === null) {
            throw new RuntimeException('Payment tenant was not found.');
        }

        return $this->currentTenant->run(
            $tenant,
            function () use ($payment, $payload, $data, $event, $transactionId, $status, $eventAmountMinor): bool {
                return DB::transaction(function () use ($payment, $payload, $data, $event, $transactionId, $status, $eventAmountMinor): bool {
                    $inserted = DB::table('payment_webhook_events')->insertOrIgnore([
                        'tenant_id' => $payment->tenant_id,
                        'provider' => 'kashier',
                        'transaction_id' => $transactionId,
                        'status' => $status,
                        'event' => $event,
                        'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                        'processed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($inserted === 0) {
                        return false;
                    }

                    $lockedPayment = Payment::withoutGlobalScopes()
                        ->whereKey($payment->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $event === 'partial_refund'
                        && $status === 'SUCCESS'
                        && $eventAmountMinor > (int) $lockedPayment->amount_minor - (int) $lockedPayment->refunded_amount_minor
                    ) {
                        throw new RuntimeException('Kashier partial refund exceeds the remaining refundable amount.');
                    }

                    $mapped = $this->mapResult($lockedPayment, $event, $status, $data, $eventAmountMinor);

                    $updated = $this->payments->applyResult($lockedPayment, $mapped);

                    $this->bookingPaymentSync->handle($updated, $updated->status);
                    $this->subscriptionPaymentSync->handle($updated, $updated->status);

                    return true;
                });
            },
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function mapResult(
        Payment $payment,
        string $event,
        string $status,
        array $data,
        int $eventAmountMinor,
    ): PaymentGatewayResult {
        $isRefundEvent = in_array($event, ['refund', 'partial_refund'], true);
        $refundSucceeded = $isRefundEvent && $status === 'SUCCESS';
        $cumulativeRefundMinor = (int) $payment->refunded_amount_minor;

        if ($event === 'refund' && $refundSucceeded) {
            $cumulativeRefundMinor = (int) $payment->amount_minor;
        } elseif ($event === 'partial_refund' && $refundSucceeded) {
            $cumulativeRefundMinor += $eventAmountMinor;
        }

        $isPaymentOperation = in_array($event, ['pay', 'capture'], true);
        $isInFlight = in_array($payment->status, [
            PaymentStatus::Pending,
            PaymentStatus::Processing,
        ], true);
        $isTerminal = in_array($payment->status, [
            PaymentStatus::Cancelled,
            PaymentStatus::Refunded,
        ], true);

        $mappedStatus = match (true) {
            $event === 'refund' && $refundSucceeded => PaymentStatus::Refunded,
            $event === 'partial_refund' && $refundSucceeded && $payment->status === PaymentStatus::Paid && $cumulativeRefundMinor >= (int) $payment->amount_minor => PaymentStatus::Refunded,
            $event === 'partial_refund' && $refundSucceeded && $payment->status === PaymentStatus::Paid => PaymentStatus::Paid,
            $isRefundEvent => $payment->status,
            $isTerminal => $payment->status,
            $event === 'reversal' && $status === 'SUCCESS' && $payment->status === PaymentStatus::Paid => PaymentStatus::Refunded,
            $event === 'reversal' && $status === 'SUCCESS' && $isInFlight => PaymentStatus::Failed,
            $event === 'void' && $status === 'SUCCESS' && $isInFlight => PaymentStatus::Cancelled,
            $event === 'reject' && $status === 'SUCCESS' && $isInFlight => PaymentStatus::Failed,
            $event === 'authorize' && $status === 'SUCCESS' && $isInFlight => PaymentStatus::Processing,
            $isPaymentOperation && $status === 'SUCCESS' => PaymentStatus::Paid,
            in_array($event, ['pay', 'capture', 'authorize', 'reject'], true) && $status === 'FAILURE' && $isInFlight => PaymentStatus::Failed,
            $status === 'PENDING' && $isInFlight => PaymentStatus::Processing,
            default => $payment->status,
        };

        return new PaymentGatewayResult(
            status: $mappedStatus,
            providerReference: $payment->provider_reference,
            method: isset($data['method']) ? (string) $data['method'] : null,
            metadata: [
                'kashier_event' => $event,
                'kashier_transaction_id' => $data['transactionId'] ?? null,
                'kashier_order_id' => $data['kashierOrderId'] ?? null,
                'kashier_merchant_order_id' => $data['merchantOrderId'] ?? null,
                'kashier_status' => $status,
                'transaction_response_code' => $data['transactionResponseCode'] ?? null,
                'kashier_refund_status' => $isRefundEvent ? $status : null,
                'refunded_amount_minor' => $cumulativeRefundMinor,
                'refund_event_amount_minor' => $isRefundEvent ? $eventAmountMinor : null,
            ],
            paidAt: $isPaymentOperation && $status === 'SUCCESS' && $mappedStatus === PaymentStatus::Paid
                ? $this->parseDate($data['creationDate'] ?? null) ?? CarbonImmutable::now('UTC')
                : null,
        );
    }

    private function amountToMinor(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Kashier webhook amount is invalid.');
        }

        [$major, $minor] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $major) * 100 + (int) str_pad(substr($minor.'00', 0, 2), 2, '0');
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
