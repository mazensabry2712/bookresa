<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

final class DashboardController
{
    public function index(CurrentTenant $currentTenant, CalculateSubscriptionUsage $usageCalculator): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $timezone = (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC'));
        $staffId = null;

        if (auth()->user()?->hasRole('staff')) {
            $staffId = StaffProfile::query()
                ->where('user_id', auth()->id())
                ->value('id');
        }

        $todayStart = CarbonImmutable::now($timezone)->startOfDay();
        $todayEnd = $todayStart->endOfDay();
        $nowUtc = CarbonImmutable::now('UTC');

        $bookingScope = fn ($query) => $query
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Rescheduled->value,
            ])
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId));

        $todayBookings = Booking::query()
            ->whereBetween('starts_at', [$todayStart->utc(), $todayEnd->utc()])
            ->whereNotIn('status', [BookingStatus::Cancelled->value])
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId))
            ->count();

        $todayRevenueMinor = Payment::query()
            ->where('payable_type', (new Booking)->getMorphClass())
            ->where('status', PaymentStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$todayStart->utc(), $todayEnd->utc()])
            ->when(
                $staffId !== null,
                fn ($query) => $query->whereHasMorph(
                    'payable',
                    [Booking::class],
                    fn ($query) => $query->where('staff_id', $staffId),
                ),
            )
            ->sum('amount_minor');

        $upcomingBookingsQuery = Booking::query()
            ->with([
                'customer:id,name,phone',
                'service:id,name,price_minor,currency',
                'staff:id,display_name',
            ])
            ->where('starts_at', '>=', $nowUtc)
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::Completed->value,
                BookingStatus::NoShow->value,
            ])
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId));

        $upcomingBookingsCount = (clone $upcomingBookingsQuery)->count();

        $upcomingBookings = $upcomingBookingsQuery
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $newCustomersToday = Customer::query()
            ->whereBetween('created_at', [$todayStart->utc(), $todayEnd->utc()])
            ->count();

        $pendingBookings = Booking::query()
            ->where('starts_at', '>=', $nowUtc)
            ->where('status', BookingStatus::Pending->value)
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId))
            ->count();

        $unpaidBookings = Booking::query()
            ->where('starts_at', '>=', $nowUtc)
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Rescheduled->value,
            ])
            ->whereIn('payment_status', [
                BookingPaymentStatus::Unpaid->value,
                BookingPaymentStatus::PartiallyPaid->value,
            ])
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId))
            ->count();

        $subscription = Subscription::query()
            ->with(['plan'])
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first();

        $usageSummary = $subscription ? $usageCalculator->handle($subscription) : null;

        $usagePercent = null;

        if ($usageSummary !== null) {
            $usageLimit = (int) $usageSummary->includedCustomerLimit;

            $usagePercent = $usageLimit > 0
                ? min(100, (int) round(($usageSummary->uniqueCustomerCount / $usageLimit) * 100))
                : ($usageSummary->uniqueCustomerCount > 0 ? 100 : 0);
        }

        $subscriptionDaysRemaining = null;

        if ($subscription?->end_at !== null) {
            $subscriptionDaysRemaining = max(
                0,
                (int) $todayStart->diffInDays(
                    $subscription->end_at->setTimezone($timezone)->startOfDay(),
                    false,
                ),
            );
        }

        return view('dashboard', [
            'tenant' => $tenant,
            'timezone' => $timezone,
            'todayLabel' => $todayStart->isoFormat('dddd, D MMMM YYYY'),
            'metrics' => [
                'todayBookings' => (int) $todayBookings,
                'todayRevenueMinor' => (int) $todayRevenueMinor,
                'upcomingBookings' => (int) $upcomingBookingsCount,
                'newCustomersToday' => (int) $newCustomersToday,
                'activeStaff' => auth()->user()?->hasRole('staff')
                    ? 1
                    : StaffProfile::query()->where('status', StaffStatus::Active)->count(),
            ],
            'upcomingBookings' => $upcomingBookings,
            'attention' => [
                'pendingBookings' => (int) $pendingBookings,
                'unpaidBookings' => (int) $unpaidBookings,
                'usagePercent' => $usagePercent,
                'usageOverLimit' => (bool) ($usageSummary?->additionalCustomerCount > 0),
                'subscriptionNeedsAction' => $subscription === null || ($subscriptionDaysRemaining !== null && $subscriptionDaysRemaining <= 7),
            ],
            'subscription' => $subscription,
            'subscriptionDaysRemaining' => $subscriptionDaysRemaining,
            'usageSummary' => $usageSummary,
        ]);
    }
}
