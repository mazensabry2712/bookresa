<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Booking\Enums\BookingStatus;
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
            $staffId = StaffProfile::query()->where('user_id', auth()->id())->value('id');
        }

        $todayStart = CarbonImmutable::now($timezone)->startOfDay();
        $todayEnd = $todayStart->endOfDay();

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
            ->when($staffId !== null, fn ($query) => $query->whereHasMorph('payable', [Booking::class], fn ($query) => $query->where('staff_id', $staffId)))
            ->sum('amount_minor');

        $upcomingBookings = Booking::query()
            ->with(['customer:id,name,phone', 'service:id,name,price_minor,currency', 'staff:id,display_name'])
            ->where('starts_at', '>=', CarbonImmutable::now('UTC'))
            ->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Rescheduled->value,
            ])
            ->when($staffId !== null, fn ($query) => $query->where('staff_id', $staffId))
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $subscription = Subscription::query()
            ->with(['plan'])
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first();

        return view('dashboard', [
            'tenant' => $tenant,
            'timezone' => $timezone,
            'metrics' => [
                'todayBookings' => (int) $todayBookings,
                'todayRevenueMinor' => (int) $todayRevenueMinor,
                'customers' => Customer::query()->count(),
                'employees' => auth()->user()?->hasRole('staff') ? 1 : StaffProfile::query()->where('status', StaffStatus::Active)->count(),
            ],
            'upcomingBookings' => $upcomingBookings,
            'subscription' => $subscription,
            'usageSummary' => $subscription ? $usageCalculator->handle($subscription) : null,
        ]);
    }
}
