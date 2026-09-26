<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

test('branded error pages are available for the core HTTP error states', function (): void {
    Route::get('/__test/403', fn () => abort(403));
    Route::get('/__test/419', fn () => abort(419));
    Route::get('/__test/429', fn () => abort(429));

    $this->get('/__test/403')
        ->assertForbidden()
        ->assertSee('Access denied');

    $this->get('/__test/419')
        ->assertStatus(419)
        ->assertSee('Page expired');

    $this->get('/__test/429')
        ->assertStatus(429)
        ->assertSee('Too many requests');

    $this->get('/__test/page-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found');

    foreach (['403', '404', '419', '429', '500', '503'] as $status) {
        expect(View::exists('errors.'.$status))->toBeTrue();
    }
});

test('branded 500 error page renders as a server error', function (): void {
    Route::get('/__test/500', fn () => abort(500));

    $this->get('/__test/500')
        ->assertInternalServerError()
        ->assertSee('Something went wrong');
});

test('branded 503 error page renders as service unavailable', function (): void {
    Route::get('/__test/503', fn () => abort(503));

    $this->get('/__test/503')
        ->assertServiceUnavailable()
        ->assertSee('Temporarily unavailable');
});

test('error pages follow the selected Arabic locale', function (): void {
    $this->get('/__test/page-that-does-not-exist?locale=ar')
        ->assertNotFound()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('الصفحة غير موجودة')
        ->assertSee('الصفحة التي تبحث عنها غير موجودة أو ربما تم نقلها.');
});

