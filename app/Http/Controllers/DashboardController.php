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
        $tenant->loadMissing('profile');

        $timezone = (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC'));
        $isStaff = auth()->user()?->hasRole('staff') ?? false;
        $staffId = null;

        if ($isStaff) {
            $staffId = StaffProfile::query()
                ->where('user_id', auth()->id())
                ->value('id');

            abort_unless($staffId !== null, 403, 'A staff profile is required for this workspace.');
        }

        $canViewBilling = auth()->user()?->can('billing.view') ?? false;

        $todayStart = CarbonImmutable::now($timezone)->startOfDay();
        $todayEnd = $todayStart->endOfDay();
        $nowUtc = CarbonImmutable::now('UTC');

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

        $newCustomersTodayQuery = Customer::query()
            ->whereBetween('created_at', [$todayStart->utc(), $todayEnd->utc()]);

        if ($isStaff) {
            $newCustomersTodayQuery->whereHas(
                'bookings',
                fn ($query) => $query->where('staff_id', $staffId),
            );
        }

        $newCustomersToday = $newCustomersTodayQuery->count();

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

        $subscription = $canViewBilling
            ? Subscription::query()
                ->with(['plan'])
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                ])
                ->where('end_at', '>', $nowUtc)
                ->where(function ($query): void {
                    $query->where('status', SubscriptionStatus::Trial->value)
                        ->orWhere(function ($query): void {
                            $query->where('status', SubscriptionStatus::Active->value)
                                ->where('payment_status', PaymentStatus::Paid->value);
                        });
                })
                ->latest('start_at')
                ->first()
            : null;

        $usageSummary = $subscription ? $usageCalculator->handle($subscription) : null;

        $customerCountQuery = Customer::query();

        if ($isStaff) {
            $customerCountQuery->whereHas(
                'bookings',
                fn ($query) => $query->where('staff_id', $staffId),
            );
        }

        $totalCustomers = $usageSummary?->uniqueCustomerCount
            ?? $customerCountQuery->count();

        $onboardingCompleted = (bool) data_get($tenant->settings, 'onboarding.completed', false);
        $onboardingStep = (string) data_get($tenant->settings, 'onboarding.step', 'services');
        $onboardingCurrentStage = match ($onboardingStep) {
            'hours' => 3,
            'staff' => 4,
            default => 2,
        };
        $onboardingRoute = match ($onboardingStep) {
            'hours' => 'scheduling.index',
            'staff' => 'staff.index',
            default => 'services.index',
        };
        $businessName = (string) (
            data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? data_get($tenant->profile?->name, 'ar')
            ?? $tenant->slug
        );

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
            'businessName' => $businessName,
            'todayLabel' => $todayStart->locale(app()->getLocale())->isoFormat('dddd, D MMMM YYYY'),
            'onboarding' => [
                'completed' => $onboardingCompleted,
                'currentStage' => $onboardingCurrentStage,
                'step' => $onboardingStep,
                'route' => $onboardingRoute,
            ],
            'metrics' => [
                'todayBookings' => (int) $todayBookings,
                'todayRevenueMinor' => (int) $todayRevenueMinor,
                'upcomingBookings' => (int) $upcomingBookingsCount,
                'customers' => (int) $totalCustomers,
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
                'subscriptionNeedsAction' => $canViewBilling
                    && ($subscription === null || ($subscriptionDaysRemaining !== null && $subscriptionDaysRemaining <= 7)),
            ],
            'subscription' => $subscription,
            'subscriptionDaysRemaining' => $subscriptionDaysRemaining,
            'usageSummary' => $usageSummary,
        ]);
    }
}