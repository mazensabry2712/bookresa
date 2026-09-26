<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Billing\Services\CancelSubscription;
use App\Domain\Billing\Services\ClearPlanChange;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Billing\Services\PlanCatalog;
use App\Domain\Billing\Services\ReactivateSubscription;
use App\Domain\Billing\Services\RenewSubscription;
use App\Domain\Billing\Services\SchedulePlanChange;
use App\Domain\Payment\Services\StartSubscriptionPayment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;
use RuntimeException;

final class SubscriptionBillingController
{
    public function index(CurrentTenant $currentTenant, CalculateSubscriptionUsage $calculator, PlanCatalog $planCatalog): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()
            ->with(['plan', 'nextPlan'])
            ->latest('start_at')
            ->first();

        $usageSummary = $subscription ? $calculator->handle($subscription) : null;
        $usagePeriods = $subscription?->usagePeriods()
            ->latest('period_end')
            ->limit(12)
            ->get() ?? collect();
        $payments = $subscription?->payments()
            ->latest('id')
            ->limit(20)
            ->get([
                'id',
                'reference',
                'provider',
                'status',
                'created_at',
            ]) ?? collect();
        $plans = $planCatalog->active();

        return view('billing.subscription', [
            'tenant' => $tenant->loadMissing('profile'),
            'subscription' => $subscription,
            'payments' => $payments,
            'usageSummary' => $usageSummary,
            'usagePeriods' => $usagePeriods,
            'plans' => $plans,
        ]);
    }

    public function subscribe(
        Plan $plan,
        CreateSubscription $createSubscription,
        StartSubscriptionPayment $startPayment,
    ): RedirectResponse {
        try {
            $subscription = $createSubscription->handle($plan);

            if ($subscription->status->value === 'trial') {
                return to_route('billing.subscription')
                    ->with('status', __('app.billing_ui.trial_active'));
            }

            $payment = $startPayment->handle($subscription);

            if (blank($payment->checkout_url)) {
                throw new RuntimeException('Payment checkout could not be started.');
            }

            return redirect()->away($payment->checkout_url);
        } catch (RuntimeException|LogicException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }

    public function checkout(
        Subscription $subscription,
        StartSubscriptionPayment $startPayment,
    ): RedirectResponse {
        try {
            $payment = $startPayment->handle($subscription);

            if ($payment->status->value === 'paid') {
                return to_route('billing.subscription')
                    ->with('status', __('app.billing_ui.payment_already_completed'));
            }

            if (blank($payment->checkout_url)) {
                throw new RuntimeException('Payment checkout could not be started.');
            }

            return redirect()->away($payment->checkout_url);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }

    public function renew(
        Subscription $subscription,
        RenewSubscription $renewSubscription,
    ): RedirectResponse {
        try {
            $renewSubscription->handle($subscription);

            return to_route('billing.subscription')->with('status', __('app.billing_ui.renewed'));
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

            return back()->with('status', __('app.billing_ui.plan_change_scheduled'));
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

            return back()->with('status', __('app.billing_ui.cancellation_scheduled_flash'));
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

            return back()->with('status', __('app.billing_ui.reactivated'));
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

            return back()->with('status', __('app.billing_ui.change_cleared'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }
    }
}
