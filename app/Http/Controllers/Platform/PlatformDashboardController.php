<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

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
                'currency',
                'billing_period',
                'included_customer_limit',
                'additional_customer_price_minor',
            ]);

        $customerCounts = Customer::withoutGlobalScopes()
            ->selectRaw('tenant_id, COUNT(*) as aggregate')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id');

        $mrrByCurrency = [];
        $additionalUsageRevenueByCurrency = [];
        $overLimitBusinesses = 0;

        foreach ($activeSubscriptions as $subscription) {
            $currency = strtoupper((string) $subscription->currency);
            $mrrByCurrency[$currency] = ($mrrByCurrency[$currency] ?? 0)
                + (int) round($subscription->price_minor / $subscription->billing_period->months());

            $count = (int) ($customerCounts[$subscription->tenant_id] ?? 0);
            $additional = max($count - $subscription->included_customer_limit, 0);

            if ($additional > 0) {
                $overLimitBusinesses++;
                $additionalUsageRevenueByCurrency[$currency] = ($additionalUsageRevenueByCurrency[$currency] ?? 0)
                    + ($additional * $subscription->additional_customer_price_minor);
            }
        }

        $bookingRevenueByCurrency = Payment::withoutGlobalScopes()
            ->where('payable_type', Booking::class)
            ->where('status', PaymentStatus::Paid)
            ->selectRaw('currency, SUM(amount_minor) as total_minor, COUNT(*) as payment_count')
            ->groupBy('currency')
            ->orderByDesc('total_minor')
            ->get();

        $subscriptionRevenueByCurrency = Payment::withoutGlobalScopes()
            ->where('payable_type', Subscription::class)
            ->where('status', PaymentStatus::Paid)
            ->selectRaw('currency, SUM(amount_minor) as total_minor, COUNT(*) as payment_count')
            ->groupBy('currency')
            ->orderByDesc('total_minor')
            ->get();

        $failedPayments = Payment::withoutGlobalScopes()
            ->where('status', PaymentStatus::Failed)
            ->count();

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
                'users' => User::query()->count(),
                'newWorkspaces30d' => Tenant::query()->where('created_at', '>=', now()->subDays(30))->count(),
                'newCustomers30d' => Customer::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(30))->count(),
                'bookings30d' => Booking::withoutGlobalScopes()->where('created_at', '>=', now()->subDays(30))->count(),
                'failedPayments' => $failedPayments,
                'paidSubscriptionRevenueMinor' => Payment::withoutGlobalScopes()
                    ->where('payable_type', Subscription::class)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount_minor'),
                'bookingRevenueMinor' => Payment::withoutGlobalScopes()
                    ->where('payable_type', Booking::class)
                    ->where('status', PaymentStatus::Paid)
                    ->sum('amount_minor'),
                'connectedPaymentAccounts' => TenantPaymentAccount::withoutGlobalScopes()
                    ->where('provider', config('bookresa.payments.default_provider', 'kashier'))
                    ->where('status', 'active')
                    ->count(),
                'usageRevenueMinor' => Subscription::withoutGlobalScopes()
                    ->whereIn('subscriptions.status', $activeSubscriptionStatuses)
                    ->join('usage_periods', 'subscriptions.id', '=', 'usage_periods.subscription_id')
                    ->sum('usage_periods.usage_charge_minor'),
                'mrrByCurrency' => $mrrByCurrency,
                'additionalUsageRevenueByCurrency' => $additionalUsageRevenueByCurrency,
                'overLimitBusinesses' => $overLimitBusinesses,
            ],
            'bookingRevenueByCurrency' => $bookingRevenueByCurrency,
            'subscriptionRevenueByCurrency' => $subscriptionRevenueByCurrency,
            'recentBusinesses' => Tenant::query()
                ->with([
                    'profile' => fn ($query) => $query->withoutGlobalScopes(),
                    'businessType',
                ])
                ->withCount([
                    'memberships' => fn ($query) => $query->withoutGlobalScopes(),
                    'services' => fn ($query) => $query->withoutGlobalScopes(),
                    'staffProfiles' => fn ($query) => $query->withoutGlobalScopes(),
                    'customers' => fn ($query) => $query->withoutGlobalScopes(),
                    'bookings' => fn ($query) => $query->withoutGlobalScopes(),
                ])
                ->latest('id')
                ->limit(10)
                ->get(),
            'recentActivity' => Activity::query()
                ->where('log_name', 'security')
                ->with('causer')
                ->latest('id')
                ->limit(10)
                ->get(),
        ]);
    }
}
