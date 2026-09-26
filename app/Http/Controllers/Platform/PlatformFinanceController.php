<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformFinanceController
{
    public function subscriptions(Request $request): View
    {
        $status = trim((string) $request->input('status'));

        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::withoutGlobalScopes()
                ->with(['tenant.profile', 'plan'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function toggleSubscriptionStatus(
        int $subscriptionId,
        AuditLogger $audit,
    ): RedirectResponse {
        $subscription = Subscription::withoutGlobalScopes()->findOrFail($subscriptionId);
        $from = $subscription->status;
        $next = in_array($from, [
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
        ], true)
            ? SubscriptionStatus::Suspended
            : ($from === SubscriptionStatus::Suspended ? SubscriptionStatus::Active : null);

        if ($next === null) {
            return back()->withErrors(['subscription' => __('Only active, trial or suspended subscriptions can be toggled by platform admin.')]);
        }

        Subscription::withoutEvents(
            fn (): bool => $subscription->forceFill(['status' => $next])->save(),
        );

        $audit->log(
            $next === SubscriptionStatus::Suspended
                ? 'platform.subscription_suspended'
                : 'platform.subscription_activated',
            $subscription,
            [
                'subscription_id' => (int) $subscription->getKey(),
                'tenant_id' => (int) $subscription->tenant_id,
                'from_status' => $from->value,
                'status' => $next->value,
            ],
        );

        return back()->with('status', $next === SubscriptionStatus::Suspended
            ? __('Subscription suspended successfully.')
            : __('Subscription activated successfully.'));
    }

    public function payments(Request $request): View
    {
        $status = trim((string) $request->input('status'));
        $provider = trim((string) $request->input('provider'));

        return view('admin.payments.index', [
            'payments' => Payment::withoutGlobalScopes()
                ->with('tenant.profile')
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($provider !== '', fn ($query) => $query->where('provider', $provider))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'providers' => Payment::withoutGlobalScopes()
                ->whereNotNull('provider')
                ->distinct()
                ->orderBy('provider')
                ->pluck('provider'),
        ]);
    }

    public function usage(Request $request): View
    {
        $status = trim((string) $request->input('status'));

        return view('admin.usage.index', [
            'usagePeriods' => UsagePeriod::withoutGlobalScopes()
                ->with(['tenant.profile', 'subscription.plan'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
