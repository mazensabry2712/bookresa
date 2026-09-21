<?php

use App\Http\Controllers\Onboarding\BusinessOnboardingController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\Booking\BookingManagementController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Payment\KashierWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/onboarding/business', [BusinessOnboardingController::class, 'create'])
        ->name('onboarding.business.create');

    Route::post('/onboarding/business', [BusinessOnboardingController::class, 'store'])
        ->name('onboarding.business.store');
});

Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('/onboarding/workspace', [BusinessOnboardingController::class, 'workspace'])
        ->name('onboarding.workspace');
});


Route::middleware(['auth', 'tenant', 'permission:bookings.view'])
    ->prefix('dashboard/bookings')
    ->group(function (): void {
        Route::get('/', [BookingManagementController::class, 'index'])
            ->name('booking.management.index');

        Route::get('/{booking}', [BookingManagementController::class, 'show'])
            ->name('booking.management.show');

        Route::post('/{booking}/status', [BookingManagementController::class, 'status'])
            ->name('booking.management.status');
    });

Route::middleware(['auth', 'tenant', 'permission:calendar.view'])
    ->get('/dashboard/calendar', [CalendarController::class, 'index'])
    ->name('calendar.index');

Route::post('/webhooks/kashier', KashierWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.kashier');

Route::prefix('book/{tenant:slug}')->group(function (): void {
    Route::get('/', [PublicBookingController::class, 'show'])
        ->name('public.booking.show');

    Route::get('/availability', [PublicBookingController::class, 'availability'])
        ->name('public.booking.availability');

    Route::post('/bookings', [PublicBookingController::class, 'store'])
        ->name('public.booking.store');

    Route::get('/confirmation/{booking}', [PublicBookingController::class, 'confirmation'])
        ->name('public.booking.confirmation');
});
