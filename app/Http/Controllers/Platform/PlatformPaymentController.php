<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
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
    ): RedirectResponse {
        $payment = Payment::withoutGlobalScopes()->findOrFail($paymentId);

        if (blank($payment->provider_reference)) {
            return back()->withErrors(['payment' => __('Payment has no provider reference to verify.')]);
        }

        $tenant = $payment->tenant()->withoutGlobalScopes()->firstOrFail();

        $result = $gateway->verifyPayment((string) $payment->provider_reference);

        $currentTenant->run($tenant, function () use ($payment, $result): void {
            app(\App\Domain\Payment\Services\PaymentService::class)->applyResult($payment, $result);
        });

        $audit->log('platform.payment_verified', $payment, [
            'tenant_id' => (int) $tenant->getKey(),
            'payment_id' => (int) $payment->getKey(),
            'result_status' => $result->status->value,
        ]);

        return back()->with('status', __('Payment verified with provider.'));
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

        if ($payment->amount_minor <= 0) {
            return back()->withErrors(['payment' => __('Payment amount is invalid for refund.')]);
        }

        $tenant = $payment->tenant()->withoutGlobalScopes()->firstOrFail();
        $result = $gateway->refundPayment((string) $payment->provider_reference, (int) $payment->amount_minor);

        $currentTenant->run($tenant, function () use ($payment, $result): void {
            app(\App\Domain\Payment\Services\PaymentService::class)->applyResult($payment, $result);
        });

        $audit->log('platform.payment_refunded', $payment, [
            'tenant_id' => (int) $tenant->getKey(),
            'payment_id' => (int) $payment->getKey(),
            'amount_minor' => (int) $payment->amount_minor,
            'currency' => $payment->currency,
        ]);

        return back()->with('status', __('Payment refund requested successfully.'));
    }
}
