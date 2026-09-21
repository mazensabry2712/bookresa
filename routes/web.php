<?php

use App\Http\Controllers\Onboarding\BusinessOnboardingController;
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
