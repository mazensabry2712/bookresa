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

        $subscriptionTenantIds = Subscription::withoutGlobalScopes()
            ->whereIn('status', $activeSubscriptionStatuses)
            ->select('tenant_id');

        return view('admin.dashboard', [
            'metrics' => [
                'businesses' => Tenant::query()->count(),
                'activeBusinesses' => Tenant::query()->where('status', TenantStatus::Active)->count(),
                'suspendedBusinesses' => Tenant::query()->where('status', TenantStatus::Suspended)->count(),
                'trialBusinesses' => Tenant::query()
                    ->whereIn('id', $subscriptionTenantIds)
                    ->whereHas('subscriptions', fn ($query) => $query->withoutGlobalScopes()->where('status', SubscriptionStatus::Trial))
                    ->count(),
                'subscriptions' => Subscription::withoutGlobalScopes()->count(),
                'activeSubscriptions' => Subscription::withoutGlobalScopes()
                    ->whereIn('status', $activeSubscriptionStatuses)
                    ->count(),
                'bookings' => Booking::withoutGlobalScopes()->count(),
                'customers' => Customer::withoutGlobalScopes()->count(),
                'paidSubscriptionRevenueMinor' => Payment::withoutGlobalScopes()
                    ->where('payable_type', Subscription::class)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount_minor'),
                'usageRevenueMinor' => Subscription::withoutGlobalScopes()
                    ->whereIn('status', $activeSubscriptionStatuses)
                    ->join('usage_periods', 'subscriptions.id', '=', 'usage_periods.subscription_id')
                    ->sum('usage_periods.usage_charge_minor'),
            ],
            'recentBusinesses' => Tenant::query()
                ->with(['profile', 'businessType'])
                ->withCount(['memberships', 'services', 'staffProfiles'])
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
}
