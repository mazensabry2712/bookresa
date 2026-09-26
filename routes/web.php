<?php

use App\Http\Controllers\Billing\SubscriptionBillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Booking\BookingManagementController;
use App\Http\Controllers\Business\BusinessProfileController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Customer\CustomerManagementController;
use App\Http\Controllers\Onboarding\BusinessOnboardingController;
use App\Http\Controllers\Payment\KashierReturnController;
use App\Http\Controllers\Payment\PaymentManagementController;
use App\Http\Controllers\Payment\KashierWebhookController;
use App\Http\Controllers\Platform\PlanAdminController;
use App\Http\Controllers\Platform\PlatformBusinessController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformFinanceController;
use App\Http\Controllers\Platform\PlatformModuleController;
use App\Http\Controllers\Platform\PlatformSettingsController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\Platform\SupportTicketController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Scheduling\SchedulingManagementController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Service\ServiceManagementController;
use App\Http\Controllers\Staff\StaffManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots.txt');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/onboarding/business', [BusinessOnboardingController::class, 'create'])
        ->name('onboarding.business.create');

    Route::post('/onboarding/business', [BusinessOnboardingController::class, 'store'])
        ->name('onboarding.business.store');
});

Route::middleware(['auth', 'verified', 'tenant'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/onboarding/workspace', [BusinessOnboardingController::class, 'workspace'])
        ->name('onboarding.workspace');

    Route::post('/onboarding/workspace/modules', [BusinessOnboardingController::class, 'updateModules'])
        ->middleware('permission:settings.manage')
        ->name('onboarding.workspace.modules');

    Route::post('/onboarding/complete', [BusinessOnboardingController::class, 'complete'])
        ->middleware('permission:settings.manage')
        ->name('onboarding.complete');

    Route::get('/dashboard/reports', [ReportController::class, 'business'])
        ->middleware('permission:reports.view')
        ->name('reports.business');

    Route::get('/dashboard/business', [BusinessProfileController::class, 'edit'])
        ->middleware('permission:business.view')
        ->name('business.profile.edit');

    Route::put('/dashboard/business', [BusinessProfileController::class, 'update'])
        ->middleware('permission:business.update')
        ->name('business.profile.update');

    Route::get('/dashboard/services', [ServiceManagementController::class, 'index'])
        ->middleware(['module:services', 'permission:services.view'])
        ->name('services.index');

    Route::post('/dashboard/services', [ServiceManagementController::class, 'store'])
        ->middleware(['module:services', 'permission:services.create'])
        ->name('services.store');

    Route::put('/dashboard/services/{service}', [ServiceManagementController::class, 'update'])
        ->middleware(['module:services', 'permission:services.update'])
        ->name('services.update');

    Route::delete('/dashboard/services/{service}', [ServiceManagementController::class, 'destroy'])
        ->middleware(['module:services', 'permission:services.delete'])
        ->name('services.destroy');

    Route::middleware(['module:customers'])->prefix('dashboard/customers')->group(function (): void {
        Route::get('/', [CustomerManagementController::class, 'index'])
            ->middleware('permission:customers.view')
            ->name('customers.index');
        Route::post('/', [CustomerManagementController::class, 'store'])
            ->middleware('permission:customers.create')
            ->name('customers.store');
        Route::get('/{customer}', [CustomerManagementController::class, 'show'])
            ->middleware('permission:customers.view')
            ->name('customers.show');
        Route::put('/{customer}', [CustomerManagementController::class, 'update'])
            ->middleware('permission:customers.update')
            ->name('customers.update');
    });

    Route::get('/dashboard/staff', [StaffManagementController::class, 'index'])
        ->middleware(['module:staff', 'permission:staff.view'])
        ->name('staff.index');

    Route::post('/dashboard/staff', [StaffManagementController::class, 'store'])
        ->middleware(['module:staff', 'permission:staff.manage'])
        ->name('staff.store');

    Route::put('/dashboard/staff/{staff}', [StaffManagementController::class, 'update'])
        ->middleware(['module:staff', 'permission:staff.manage'])
        ->name('staff.update');

    Route::patch('/dashboard/staff/{staff}/status', [StaffManagementController::class, 'status'])
        ->middleware(['module:staff', 'permission:staff.manage'])
        ->name('staff.status');

    Route::middleware(['module:calendar'])->prefix('dashboard/scheduling')->group(function (): void {
        Route::get('/', [SchedulingManagementController::class, 'index'])
            ->middleware('permission:calendar.view')
            ->name('scheduling.index');

        Route::put('/business-hours', [SchedulingManagementController::class, 'updateBusinessHours'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.business-hours.update');

        Route::post('/breaks', [SchedulingManagementController::class, 'storeBreak'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.breaks.store');

        Route::delete('/breaks/{break}', [SchedulingManagementController::class, 'destroyBreak'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.breaks.destroy');

        Route::post('/holidays', [SchedulingManagementController::class, 'storeHoliday'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.holidays.store');

        Route::delete('/holidays/{holiday}', [SchedulingManagementController::class, 'destroyHoliday'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.holidays.destroy');

        Route::post('/special-hours', [SchedulingManagementController::class, 'storeSpecialWorkingHour'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.special-hours.store');

        Route::delete('/special-hours/{specialWorkingHour}', [SchedulingManagementController::class, 'destroySpecialWorkingHour'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.special-hours.destroy');

        Route::put('/staff/{staff}/hours', [SchedulingManagementController::class, 'updateStaffHours'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.staff-hours.update');

        Route::post('/staff/{staff}/days-off', [SchedulingManagementController::class, 'storeDayOff'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.staff-days-off.store');

        Route::delete('/staff/{staff}/days-off/{dayOff}', [SchedulingManagementController::class, 'destroyDayOff'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.staff-days-off.destroy');

        Route::post('/staff/{staff}/availability', [SchedulingManagementController::class, 'storeAvailability'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.staff-availability.store');

        Route::delete('/staff/{staff}/availability/{availability}', [SchedulingManagementController::class, 'destroyAvailability'])
            ->middleware('permission:settings.manage')
            ->name('scheduling.staff-availability.destroy');
    });
});

Route::middleware(['auth', 'verified', 'tenant', 'module:appointments', 'permission:bookings.view'])
    ->prefix('dashboard/bookings')
    ->group(function (): void {
        Route::get('/', [BookingManagementController::class, 'index'])
            ->name('booking.management.index');

        Route::get('/{booking}', [BookingManagementController::class, 'show'])
            ->name('booking.management.show');

        Route::post('/{booking}/status', [BookingManagementController::class, 'status'])
            ->name('booking.management.status');

        Route::post('/{booking}/reschedule', [BookingManagementController::class, 'reschedule'])
            ->name('booking.management.reschedule');
    });

Route::middleware(['auth', 'verified', 'tenant', 'module:calendar', 'permission:calendar.view'])
    ->get('/dashboard/calendar', [CalendarController::class, 'index'])
    ->name('calendar.index');

Route::middleware(['auth', 'verified', 'tenant'])->group(function (): void {
    Route::get('/dashboard/payments', [PaymentManagementController::class, 'index'])
        ->middleware(['module:payments', 'permission:billing.view'])
        ->name('payments.index');
});

Route::middleware(['auth', 'verified', 'tenant'])->prefix('dashboard/billing')->group(function (): void {
    Route::get('/subscription', [SubscriptionBillingController::class, 'index'])
        ->middleware('permission:billing.view')
        ->name('billing.subscription');

    Route::post('/plans/{plan}/subscribe', [SubscriptionBillingController::class, 'subscribe'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscribe');

    Route::post('/subscription/{subscription}/checkout', [SubscriptionBillingController::class, 'checkout'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.checkout');

    Route::post('/subscription/{subscription}/renew', [SubscriptionBillingController::class, 'renew'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.renew');

    Route::post('/subscription/{subscription}/plan', [SubscriptionBillingController::class, 'changePlan'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.plan');

    Route::post('/subscription/{subscription}/cancel', [SubscriptionBillingController::class, 'cancel'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.cancel');

    Route::post('/subscription/{subscription}/reactivate', [SubscriptionBillingController::class, 'reactivate'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.reactivate');

    Route::post('/subscription/{subscription}/plan/clear', [SubscriptionBillingController::class, 'clearPlanChange'])
        ->middleware('permission:subscription.manage')
        ->name('billing.subscription.plan.clear');
});

Route::middleware(['auth', 'verified', 'platform'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::get('/businesses', [PlatformBusinessController::class, 'index'])->name('businesses.index');
        Route::get('/businesses/{tenant}/modules', [PlatformModuleController::class, 'index'])->name('businesses.modules.index');
        Route::put('/businesses/{tenant}/modules', [PlatformModuleController::class, 'update'])->name('businesses.modules.update');
        Route::get('/users', [PlatformUserController::class, 'index'])->name('users.index');
        Route::patch('/users/memberships/{membership}/toggle', [PlatformUserController::class, 'toggleMembership'])
            ->name('users.membership-toggle');
        Route::patch('/users/{user}/platform-admin/toggle', [PlatformUserController::class, 'togglePlatformAdmin'])
            ->name('users.platform-admin-toggle');
        Route::get('/subscriptions', [PlatformFinanceController::class, 'subscriptions'])->name('subscriptions.index');
        Route::get('/payments', [PlatformFinanceController::class, 'payments'])->name('payments.index');
        Route::get('/usage', [PlatformFinanceController::class, 'usage'])->name('usage.index');
        Route::get('/reports', [ReportController::class, 'platform'])->name('reports.index');
        Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
        Route::get('/settings', [PlatformSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [PlatformSettingsController::class, 'update'])->name('settings.update');
        Route::patch('/support/{ticket}', [SupportTicketController::class, 'update'])->name('support.update');
        Route::patch('/businesses/{tenant}/status', [PlatformBusinessController::class, 'toggleStatus'])
            ->name('businesses.toggle-status');
        Route::get('/plans', [PlanAdminController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [PlanAdminController::class, 'create'])->name('plans.create');
        Route::post('/plans', [PlanAdminController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [PlanAdminController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlanAdminController::class, 'update'])->name('plans.update');
        Route::patch('/plans/{plan}/toggle', [PlanAdminController::class, 'toggle'])->name('plans.toggle');
    });

Route::post('/webhooks/kashier', KashierWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.kashier');

Route::match(['get', 'post'], '/payments/kashier/return', KashierReturnController::class)
    ->name('payments.kashier.return');

Route::middleware('module:appointments')->prefix('book/{tenant:slug}')->group(function (): void {
    Route::get('/', [PublicBookingController::class, 'show'])
        ->name('public.booking.show');

    Route::get('/availability', [PublicBookingController::class, 'availability'])
        ->middleware('throttle:60,1')
        ->name('public.booking.availability');

    Route::post('/bookings', [PublicBookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('public.booking.store');

    Route::get('/confirmation/{booking}', [PublicBookingController::class, 'confirmation'])
        ->middleware('signed')
        ->name('public.booking.confirmation');
});
