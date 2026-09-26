<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\View\View;

final class PlatformDashboardController
{
    public function index(): View
    {
        $activeSubscriptionStatuses = [
            SubscriptionStatus::Trial->value,
            SubscriptionStatus::Active->value,
        ];
        $paidActiveSubscriptionStatuses = [SubscriptionStatus::Active->value];

        $subscriptionTenantIds = Subscription::withoutGlobalScopes()
            ->whereIn('subscriptions.status', $activeSubscriptionStatuses)
            ->select('tenant_id');

        $activeSubscriptions = Subscription::withoutGlobalScopes()
            ->whereIn('status', $paidActiveSubscriptionStatuses)
            ->where('payment_status', PaymentStatus::Paid)
            ->get([
                'tenant_id',
                'price_minor',
                'billing_period',
                'included_customer_limit',
                'additional_customer_price_minor',
            ]);

        $customerCounts = Customer::withoutGlobalScopes()
            ->selectRaw('tenant_id, COUNT(*) as aggregate')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id');

        $mrrMinor = (int) $activeSubscriptions->sum(
            fn (Subscription $subscription): int => (int) round(
                $subscription->price_minor / $subscription->billing_period->months(),
            ),
        );

        $overLimitBusinesses = 0;
        $additionalUsageRevenueMinor = 0;

        foreach ($activeSubscriptions as $subscription) {
            $count = (int) ($customerCounts[$subscription->tenant_id] ?? 0);
            $additional = max($count - $subscription->included_customer_limit, 0);

            if ($additional > 0) {
                $overLimitBusinesses++;
                $additionalUsageRevenueMinor += $additional * $subscription->additional_customer_price_minor;
            }
        }

        return view('admin.dashboard', [
            'metrics' => [
                'businesses' => Tenant::query()->count(),
                'activeBusinesses' => Tenant::query()->where('status', TenantStatus::Active)->count(),
                'suspendedBusinesses' => Tenant::query()->where('status', TenantStatus::Suspended)->count(),
                'expiredBusinesses' => Tenant::query()
                    ->whereHas('subscriptions', fn ($query) => $query->withoutGlobalScopes()->where('subscriptions.status', SubscriptionStatus::Expired))
                    ->count(),
                'trialBusinesses' => Tenant::query()
                    ->whereIn('id', $subscriptionTenantIds)
                    ->whereHas('subscriptions', fn ($query) => $query->withoutGlobalScopes()->where('subscriptions.status', SubscriptionStatus::Trial))
                    ->count(),
                'subscriptions' => Subscription::withoutGlobalScopes()->count(),
                'activeSubscriptions' => Subscription::withoutGlobalScopes()
                    ->whereIn('subscriptions.status', $activeSubscriptionStatuses)
                    ->count(),
                'bookings' => Booking::withoutGlobalScopes()->count(),
                'customers' => Customer::withoutGlobalScopes()->count(),
                'users' => \App\Models\User::query()->count(),
                'paidSubscriptionRevenueMinor' => Payment::withoutGlobalScopes()
                    ->where('payable_type', Subscription::class)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount_minor'),
                'usageRevenueMinor' => Subscription::withoutGlobalScopes()
                    ->whereIn('subscriptions.status', $activeSubscriptionStatuses)
                    ->join('usage_periods', 'subscriptions.id', '=', 'usage_periods.subscription_id')
                    ->sum('usage_periods.usage_charge_minor'),
                'mrrMinor' => $mrrMinor,
                'overLimitBusinesses' => $overLimitBusinesses,
                'additionalUsageRevenueMinor' => $additionalUsageRevenueMinor,
                'additionalUsageRevenueMinor' => $additionalUsageRevenueMinor,
            ],
            'recentBusinesses' => Tenant::query()
                ->with([
                    'profile' => fn ($query) => $query->withoutGlobalScopes(),
                    'businessType',
                ])
                ->withCount([
                    'memberships' => fn ($query) => $query->withoutGlobalScopes(),
                    'services' => fn ($query) => $query->withoutGlobalScopes(),
                    'staffProfiles' => fn ($query) => $query->withoutGlobalScopes(),
                ])
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
}
