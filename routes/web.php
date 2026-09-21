<?php

use App\Http\Controllers\Onboarding\BusinessOnboardingController;
use App\Http\Controllers\PublicBookingController;
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
