<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Billing\Services\CancelSubscription;
use App\Domain\Billing\Services\ClearPlanChange;
use App\Domain\Billing\Services\ReactivateSubscription;
use App\Domain\Billing\Services\SchedulePlanChange;
use App\Domain\Payment\Services\StartSubscriptionPayment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class SubscriptionBillingController
{
    public function index(CurrentTenant $currentTenant, CalculateSubscriptionUsage $calculator): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()
            ->with(['plan', 'nextPlan', 'payments' => fn ($query) => $query->latest('id')])
            ->latest('start_at')
            ->first();

        $usageSummary = $subscription ? $calculator->handle($subscription) : null;
        $usagePeriods = $subscription?->usagePeriods()
            ->latest('period_end')
            ->limit(12)
            ->get() ?? collect();
        $plans = Plan::query()->active()->orderBy('price_minor')->get();

        return view('billing.subscription', [
            'tenant' => $tenant->loadMissing('profile'),
            'subscription' => $subscription,
            'payments' => $subscription?->payments ?? collect(),
            'usageSummary' => $usageSummary,
            'usagePeriods' => $usagePeriods,
            'plans' => $plans,
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

    public function changePlan(
        Request $request,
        Subscription $subscription,
        SchedulePlanChange $schedulePlanChange,
    ): RedirectResponse {
        try {
            $validated = $request->validate([
                'plan_id' => ['required', 'integer', 'exists:plans,id'],
            ]);

            $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
            $schedulePlanChange->handle($subscription, $plan);

            return back()->with('status', 'Plan change scheduled for the next billing boundary.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }

    public function cancel(
        Subscription $subscription,
        CancelSubscription $cancelSubscription,
    ): RedirectResponse {
        try {
            $cancelSubscription->handle($subscription);

            return back()->with('status', 'Subscription cancellation scheduled for the billing boundary.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }

    public function reactivate(
        Subscription $subscription,
        ReactivateSubscription $reactivateSubscription,
    ): RedirectResponse {
        try {
            $reactivateSubscription->handle($subscription);

            return back()->with('status', 'Subscription cancellation was reactivated.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }

    public function clearPlanChange(
        Subscription $subscription,
        ClearPlanChange $clearPlanChange,
    ): RedirectResponse {
        try {
            $clearPlanChange->handle($subscription);

            return back()->with('status', 'Scheduled plan change cleared.');
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }
}
