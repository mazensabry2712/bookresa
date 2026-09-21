<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Payment\Services\StartSubscriptionPayment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

final class SubscriptionBillingController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()
            ->with(['plan', 'nextPlan', 'payments' => fn ($query) => $query->latest('id')])
            ->latest('start_at')
            ->first();

        return view('billing.subscription', [
            'tenant' => $tenant->loadMissing('profile'),
            'subscription' => $subscription,
            'payments' => $subscription?->payments ?? collect(),
        ]);
    }

    public function checkout(
        Subscription $subscription,
        StartSubscriptionPayment $startPayment,
    ): RedirectResponse {
        try {
            $payment = $startPayment->handle($subscription);

            if ($payment->status->value === 'paid') {
                return to_route('billing.subscription')
                    ->with('status', 'Subscription payment is already completed.');
            }

            if (blank($payment->checkout_url)) {
                throw new RuntimeException('Payment checkout could not be started.');
            }

            return redirect()->away($payment->checkout_url);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }
}
