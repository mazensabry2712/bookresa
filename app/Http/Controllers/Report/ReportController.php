<?php

namespace App\Http\Controllers\Report;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ReportController
{
    public function business(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        [$startUtc, $endUtc, $fromDate, $toDate, $timezone] = $this->range($request, $tenant->profile?->timezone);

        $bookings = Booking::query()->whereBetween('starts_at', [$startUtc, $endUtc]);

        $statusCounts = (clone $bookings)
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $customers = (clone $bookings)
            ->distinct()
            ->count('customer_id');

        $revenueMinor = Payment::query()
            ->where('payable_type', (new Booking)->getMorphClass())
            ->where('status', PaymentStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->sum('amount_minor');

        $topServices = (clone $bookings)
            ->select('service_id', DB::raw('COUNT(*) as booking_count'))
            ->with('service')
            ->groupBy('service_id')
            ->orderByDesc('booking_count')
            ->orderBy('service_id')
            ->limit(5)
            ->get();

        return view('reports.business', [
            'tenant' => $tenant,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'timezone' => $timezone,
            'metrics' => [
                'bookings' => (int) $statusCounts->sum(),
                'customers' => $customers,
                'revenueMinor' => (int) $revenueMinor,
                'cancellations' => (int) $statusCounts->get(BookingStatus::Cancelled->value, 0),
                'noShows' => (int) $statusCounts->get(BookingStatus::NoShow->value, 0),
                'completed' => (int) $statusCounts->get(BookingStatus::Completed->value, 0),
            ],
            'statusCounts' => $statusCounts,
            'statuses' => BookingStatus::cases(),
            'topServices' => $topServices,
        ]);
    }

    public function platform(Request $request): View
    {
        [$startUtc, $endUtc, $fromDate, $toDate, $timezone] = $this->range(
            $request,
            config('app.timezone', 'UTC'),
        );

        $activeSubscriptionStatuses = [
            SubscriptionStatus::Trial->value,
            SubscriptionStatus::Active->value,
        ];

        $subscriptionRevenueMinor = Payment::withoutGlobalScopes()
            ->where('payable_type', (new Subscription)->getMorphClass())
            ->where('status', PaymentStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->sum('amount_minor');

        $usageRevenueMinor = UsagePeriod::withoutGlobalScopes()
            ->whereBetween('period_start', [$startUtc, $endUtc])
            ->sum('usage_charge_minor');

        $bookings = Booking::withoutGlobalScopes()
            ->whereBetween('starts_at', [$startUtc, $endUtc])
            ->count();

        $subscriptionsStarted = Subscription::withoutGlobalScopes()
            ->whereBetween('start_at', [$startUtc, $endUtc])
            ->count();

        $overLimitBusinesses = UsagePeriod::withoutGlobalScopes()
            ->whereBetween('period_start', [$startUtc, $endUtc])
            ->where('additional_customer_count', '>', 0)
            ->distinct()
            ->count('tenant_id');

        return view('admin.reports.index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'timezone' => $timezone,
            'metrics' => [
                'businesses' => Tenant::query()->count(),
                'newBusinesses' => Tenant::query()->whereBetween('created_at', [$startUtc, $endUtc])->count(),
                'activeBusinesses' => Tenant::query()->where('status', 'active')->count(),
                'suspendedBusinesses' => Tenant::query()->where('status', 'suspended')->count(),
                'bookings' => $bookings,
                'subscriptionsStarted' => $subscriptionsStarted,
                'activeSubscriptions' => Subscription::withoutGlobalScopes()
                    ->whereIn('subscriptions.status', $activeSubscriptionStatuses)
                    ->count(),
                'overLimitBusinesses' => $overLimitBusinesses,
                'subscriptionRevenueMinor' => (int) $subscriptionRevenueMinor,
                'usageRevenueMinor' => (int) $usageRevenueMinor,
                'platformRevenueMinor' => (int) $subscriptionRevenueMinor + (int) $usageRevenueMinor,
            ],
        ]);
    }

    private function range(Request $request, ?string $timezone): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $timezone ??= config('app.timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);

        $fromDate = $validated['from'] ?? $now->subDays(29)->toDateString();
        $toDate = $validated['to'] ?? $now->toDateString();

        $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $fromDate.' 00:00:00', $timezone);
        $end = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $toDate.' 23:59:59', $timezone);

        return [$start->utc(), $end->utc(), $fromDate, $toDate, $timezone];
    }
}
