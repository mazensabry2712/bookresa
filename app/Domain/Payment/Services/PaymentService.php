<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Booking\Models\Booking;
use App\Notifications\PaymentNotification;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AuditLogger $audit,
    ) {
    }

    /**
     * Start a provider-neutral payment and persist its local record.
     *
     * @param array<string, mixed> $metadata
     */
    public function start(
        PaymentGateway $gateway,
        Model $payable,
        int $amountMinor,
        string $currency,
        string $provider,
        ?string $description = null,
        array $metadata = [],
        ?string $idempotencyKey = null,
    ): Payment {
        $tenantId = $this->currentTenant->idOrFail();

        $payableTenantId = $payable->getAttribute('tenant_id');

        if ($payableTenantId === null || (int) $payableTenantId !== $tenantId) {
            throw new LogicException('Payment payable must belong to the current tenant.');
        }

        if ($amountMinor <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('Payment currency must be a 3-letter ISO code.');
        }

        $provider = trim($provider);

        if ($provider === '' || strlen($provider) > 32) {
            throw new RuntimeException('Payment provider is invalid.');
        }

        if ($idempotencyKey !== null) {
            $existing = Payment::query()
                ->where('provider', $provider)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                if (
                    $existing->payable_type !== $payable->getMorphClass()
                    || (int) $existing->payable_id !== (int) $payable->getKey()
                    || (int) $existing->amount_minor !== $amountMinor
                    || $existing->currency !== $currency
                ) {
                    throw new RuntimeException('Idempotency key is already used for a different payment.');
                }

                if ($existing->status !== PaymentStatus::Pending || $existing->provider_reference !== null) {
                    return $existing;
                }

                if (! isset($existing->metadata['provider_creation_failed_at'])) {
                    return $existing;
                }

                $payment = $existing;
            }
        }

        if (! isset($payment)) {
            try {
                $payment = Payment::query()->create([
                    'tenant_id' => $tenantId,
                    'payable_type' => $payable->getMorphClass(),
                    'payable_id' => $payable->getKey(),
                    'reference' => $this->reference(),
                    'provider' => $provider,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'status' => PaymentStatus::Pending,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => $metadata,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($idempotencyKey === null || ! in_array((string) $e->getCode(), ['23000', '1062'], true)) {
                    throw $e;
                }

                $payment = Payment::query()
                    ->where('provider', $provider)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($payment === null) {
                    throw $e;
                }

                if (
                    $payment->payable_type !== $payable->getMorphClass()
                    || (int) $payment->payable_id !== (int) $payable->getKey()
                    || (int) $payment->amount_minor !== $amountMinor
                    || $payment->currency !== $currency
                ) {
                    throw new RuntimeException('Idempotency key is already used for a different payment.');
                }

                if ($payment->status !== PaymentStatus::Pending || $payment->provider_reference !== null) {
                    return $payment;
                }

                if (! isset($payment->metadata['provider_creation_failed_at'])) {
                    return $payment;
                }
            }
        }

        try {
            $result = $gateway->createPayment(new PaymentRequest(
                merchantReference: $payment->reference,
                amountMinor: $amountMinor,
                currency: $currency,
                description: $description,
                metadata: $metadata,
                idempotencyKey: $idempotencyKey,
            ));

            return $this->applyResult($payment, $result);
        } catch (\Throwable $e) {
            $payment->forceFill([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'provider_creation_failed_at' => now()->toIso8601String(),
                ]),
            ])->save();

            throw $e;
        }
    }

    public function applyResult(Payment $payment, PaymentGatewayResult $result): Payment
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $payment->tenant_id !== $tenantId) {
            throw new LogicException('Payment must belong to the current tenant.');
        }

        $currentStatus = $payment->status;

        if ($currentStatus === PaymentStatus::Refunded && $result->status !== PaymentStatus::Refunded) {
            return $payment;
        }

        if (
            $currentStatus === PaymentStatus::Paid
            && ! in_array($result->status, [PaymentStatus::Paid, PaymentStatus::Refunded], true)
        ) {
            return $payment;
        }

        if (
            $currentStatus !== $result->status
            && ! $this->canTransition($currentStatus, $result->status)
        ) {
            throw new RuntimeException(
                "Invalid payment status transition from {$currentStatus->value} to {$result->status->value}.",
            );
        }

        $metadata = array_merge($payment->metadata ?? [], $result->metadata);

        $payment->forceFill([
            'provider_reference' => $result->providerReference ?? $payment->provider_reference,
            'status' => $result->status,
            'method' => $result->method ?? $payment->method,
            'checkout_url' => $result->checkoutUrl ?? $payment->checkout_url,
            'metadata' => $metadata,
            'paid_at' => $result->paidAt ?? ($result->status === PaymentStatus::Paid ? $payment->paid_at ?? now() : $payment->paid_at),
            'expires_at' => $result->expiresAt ?? $payment->expires_at,
        ])->save();

        if ($currentStatus !== $result->status) {
            $this->audit->log(
                'payment.status_changed',
                $payment,
                [
                    'tenant_id' => (int) $payment->tenant_id,
                    'payment_id' => (int) $payment->getKey(),
                    'from_status' => $currentStatus->value,
                    'to_status' => $result->status->value,
                ],
            );
        }

        if (
            $currentStatus !== $result->status
            && in_array($result->status, [PaymentStatus::Paid, PaymentStatus::Failed], true)
        ) {
            $payable = $payment->payable;

            if ($payable instanceof Booking) {
                $payable->load('customer');
                $payable->customer?->notify(new PaymentNotification(
                    $payment->fresh(),
                    $result->status === PaymentStatus::Paid ? 'paid' : 'failed',
                ));
            }
        }

        return $payment->fresh();
    }

    private function canTransition(PaymentStatus $from, PaymentStatus $to): bool
    {
        return match ($from) {
            PaymentStatus::Pending => in_array($to, [
                PaymentStatus::Processing,
                PaymentStatus::Paid,
                PaymentStatus::Failed,
                PaymentStatus::Cancelled,
            ], true),
            PaymentStatus::Processing => in_array($to, [
                PaymentStatus::Paid,
                PaymentStatus::Failed,
                PaymentStatus::Cancelled,
            ], true),
            PaymentStatus::Failed => in_array($to, [
                PaymentStatus::Processing,
                PaymentStatus::Paid,
            ], true),
            PaymentStatus::Paid => $to === PaymentStatus::Refunded,
            PaymentStatus::Cancelled => false,
            PaymentStatus::Refunded => false,
        };
    }

    private function reference(): string
    {
        do {
            $reference = 'PAY-'.strtoupper(Str::random(10));
        } while (Payment::withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }
}
