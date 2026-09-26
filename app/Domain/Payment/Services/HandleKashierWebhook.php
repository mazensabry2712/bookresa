<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\PaymentWebhookEvent;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierWebhookVerifier;
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
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload, string $signature): bool
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new RuntimeException('Invalid Kashier webhook payload.');
        }

        $event = (string) ($payload['event'] ?? '');
        $transactionId = (string) ($data['transactionId'] ?? '');
        $orderReference = (string) ($data['orderReference'] ?? '');
        $status = strtoupper((string) ($data['status'] ?? ''));

        if ($event === '' || $transactionId === '' || $orderReference === '' || ! in_array($status, ['SUCCESS', 'FAILURE', 'PENDING'], true)) {
            throw new RuntimeException('Kashier webhook payload is missing required fields.');
        }

        $apiKey = (string) config('bookresa.payments.kashier.api_key');

        if (! $this->verifier->verify($data, $signature, $apiKey)) {
            throw new LogicException('Invalid Kashier webhook signature.');
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'kashier')
            ->where('reference', $orderReference)
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Payment for Kashier order reference was not found.');
        }

        if ($this->amountToMinor($data['amount'] ?? null) !== (int) $payment->amount_minor) {
            throw new RuntimeException('Kashier webhook amount does not match the payment.');
        }

        if (strtoupper((string) ($data['currency'] ?? '')) !== strtoupper((string) $payment->currency)) {
            throw new RuntimeException('Kashier webhook currency does not match the payment.');
        }

        $tenant = $payment->tenant;

        if ($tenant === null) {
            throw new RuntimeException('Payment tenant was not found.');
        }

        return $this->currentTenant->run(
            $tenant,
            function () use ($payment, $payload, $data, $event, $transactionId, $status): bool {
                return DB::transaction(function () use ($payment, $payload, $data, $event, $transactionId, $status): bool {
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

                    $mapped = $this->mapResult($payment, $event, $status, $data);

                    $updated = $this->payments->applyResult($payment->fresh(), $mapped);

                    $this->bookingPaymentSync->handle($updated, $mapped->status);
                    $this->subscriptionPaymentSync->handle($updated, $mapped->status);

                    return true;
                });
            },
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapResult(
        Payment $payment,
        string $event,
        string $status,
        array $data,
    ): PaymentGatewayResult {
        $mappedStatus = match ($event) {
            'refund' => $status === 'SUCCESS'
                ? PaymentStatus::Refunded
                : PaymentStatus::Paid,
            'partial_refund' => PaymentStatus::Paid,
            default => match ($status) {
                'SUCCESS' => PaymentStatus::Paid,
                'FAILURE' => PaymentStatus::Failed,
                default => PaymentStatus::Processing,
            },
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
                'kashier_refund_status' => in_array($event, ['refund', 'partial_refund'], true) ? $status : null,
            ],
            paidAt: $mappedStatus === PaymentStatus::Paid
                ? $this->parseDate($data['creationDate'] ?? null) ?? CarbonImmutable::now('UTC')
                : null,
        );
    }

    private function amountToMinor(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Kashier webhook amount is invalid.');
        }

        [$major, $minor] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $major) * 100 + (int) str_pad(substr($minor.'00', 0, 2), 2, '0');
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
