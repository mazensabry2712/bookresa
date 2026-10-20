<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\SyncBookingPaymentStatus;
use App\Domain\Payment\Services\SyncSubscriptionPaymentStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PlatformPaymentController
{
    public function show(int $paymentId): View
    {
        $payment = Payment::withoutGlobalScopes()
            ->with(['tenant.profile', 'payable'])
            ->findOrFail($paymentId);

        return view('admin.payments.show', [
            'payment' => $payment,
        ]);
    }

    public function verify(
        int $paymentId,
        PaymentGateway $gateway,
        CurrentTenant $currentTenant,
        AuditLogger $audit,
        PlatformSettings $platformSettings,
    ): RedirectResponse {
        $payment = Payment::withoutGlobalScopes()->findOrFail($paymentId);

        if (blank($payment->provider_reference)) {
            return back()->withErrors(['payment' => __('Payment has no provider reference to verify.')]);
        }

        $tenant = $payment->tenant()->withoutGlobalScopes()->firstOrFail();

        try {
            $result = $gateway->verifyPayment((string) $payment->provider_reference);
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        if ($payment->provider === 'kashier') {
            $kashierConfig = $platformSettings->kashierConfig();
            $usesCentralPlatformMerchant = $payment->payable_type === (new Subscription)->getMorphClass()
                || filter_var(data_get($payment->metadata, 'centralized_payment', false), FILTER_VALIDATE_BOOL);
            $expectedMerchantId = $usesCentralPlatformMerchant
                ? (string) ($kashierConfig['merchant_id'] ?? '')
                : trim((string) data_get($payment->metadata, 'connected_account_merchant_id'));
            $returnedMerchantId = trim((string) data_get($result->metadata, 'merchant_id'));
            $returnedOrderReference = trim((string) data_get($result->metadata, 'merchant_order_id'));
            $returnedCurrency = strtoupper(trim((string) data_get($result->metadata, 'currency')));
            $verifiedAmountMinor = $this->amountToMinor(data_get($result->metadata, 'amount'));

            $verificationMatches = $returnedMerchantId !== ''
                && $expectedMerchantId !== ''
                && $returnedMerchantId === $expectedMerchantId
                && $returnedOrderReference === $payment->reference
                && $verifiedAmountMinor === (int) $payment->amount_minor
                && $returnedCurrency === strtoupper((string) $payment->currency);

            if (! $verificationMatches) {
                return back()->withErrors([
                    'payment' => __('Provider verification details do not match this payment. No changes were made.'),
                ]);
            }
        }

        $updated = $currentTenant->run($tenant, function () use ($payment, $result): Payment {
            $updated = app(PaymentService::class)->applyResult($payment, $result);
            app(SyncBookingPaymentStatus::class)->handle($updated, $updated->status);
            app(SyncSubscriptionPaymentStatus::class)->handle($updated, $updated->status);

            return $updated;
        });

        $audit->log('platform.payment_verified', $payment, [
            'tenant_id' => (int) $tenant->getKey(),
            'payment_id' => (int) $payment->getKey(),
            'result_status' => $updated->status->value,
        ]);

        return back()->with('status', __('Payment verified with provider.'));
    }

    private function amountToMinor(mixed $amount): ?int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\\d+(?:\\.\\d{1,2})?$/', $value)) {
            return null;
        }

        [$major, $minor] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $major * 100) + (int) str_pad(substr($minor.'00', 0, 2), 2, '0');
    }

    public function refund(
        int $paymentId,
        PaymentGateway $gateway,
        CurrentTenant $currentTenant,
        AuditLogger $audit,
    ): RedirectResponse {
        $payment = Payment::withoutGlobalScopes()->findOrFail($paymentId);

        if ($payment->status !== PaymentStatus::Paid) {
            return back()->withErrors(['payment' => __('Only paid payments can be refunded.')]);
        }

        if (blank($payment->provider_reference)) {
            return back()->withErrors(['payment' => __('Payment has no provider reference and cannot be refunded.')]);
        }

        $remainingRefundMinor = max(
            (int) $payment->amount_minor - (int) $payment->refunded_amount_minor,
            0,
        );

        if ($remainingRefundMinor <= 0) {
            return back()->withErrors(['payment' => __('This payment has no refundable balance left.')]);
        }

        $tenant = $payment->tenant()->withoutGlobalScopes()->firstOrFail();

        try {
            $result = $gateway->refundPayment((string) $payment->provider_reference, $remainingRefundMinor);
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        $currentTenant->run($tenant, function () use ($payment, $result): void {
            $updated = app(PaymentService::class)->applyResult($payment, $result);
            app(SyncBookingPaymentStatus::class)->handle($updated, $updated->status);
            app(SyncSubscriptionPaymentStatus::class)->handle($updated, $updated->status);
        });

        $audit->log('platform.payment_refunded', $payment, [
            'tenant_id' => (int) $tenant->getKey(),
            'payment_id' => (int) $payment->getKey(),
            'amount_minor' => $remainingRefundMinor,
            'original_amount_minor' => (int) $payment->amount_minor,
            'previously_refunded_amount_minor' => (int) $payment->refunded_amount_minor,
            'currency' => $payment->currency,
        ]);

        return back()->with('status', __('Payment refunded successfully.'));
    }
}
