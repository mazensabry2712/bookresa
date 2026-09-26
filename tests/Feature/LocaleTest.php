<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the browser preferred locale is applied to web requests', function (): void {
    $this->withHeaders([
        'Accept-Language' => 'ar-EG,ar;q=0.9,en;q=0.8',
    ])->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false);

    expect(app()->getLocale())->toBe('ar');
});

test('the session locale overrides the browser preference', function (): void {
    $this->withSession(['locale' => 'ar'])
        ->withHeaders([
            'Accept-Language' => 'en-US,en;q=0.9',
        ])
        ->get('/')
        ->assertOk()
        ->assertSee('<html lang="ar">', false);

    expect(app()->getLocale())->toBe('ar');
});

test('an invalid locale falls back to the configured default', function (): void {
    $this->withSession(['locale' => 'fr'])
        ->get('/')
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr">', false);

    expect(app()->getLocale())->toBe('en');
});

test('a valid locale query parameter persists for the session', function (): void {
    $response = $this->get('/?locale=ar');

    $response->assertOk()
        ->assertSee('<html lang="ar">', false);

    expect(session('locale'))->toBe('ar')
        ->and(app()->getLocale())->toBe('ar');
});

test('translation files expose the supported locale dictionaries', function (): void {
    app()->setLocale('en');

    expect(__('app.language'))->toBe('Language')
        ->and(__('app.english'))->toBe('English');

    app()->setLocale('ar');

    expect(__('app.language'))->toBe('اللغة')
        ->and(__('app.english'))->toBe('الإنجليزية');
});
