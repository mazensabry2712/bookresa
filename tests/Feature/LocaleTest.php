<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

test('the browser preferred locale is applied to web requests', function (): void {
    $this->withHeaders([
        'Accept-Language' => 'ar-EG,ar;q=0.9,en;q=0.8',
    ])->get('/')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);

    expect(app()->getLocale())->toBe('ar');
});

test('the session locale overrides the browser preference', function (): void {
    $this->withSession(['locale' => 'ar'])
        ->withHeaders([
            'Accept-Language' => 'en-US,en;q=0.9',
        ])
        ->get('/')
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);

    expect(app()->getLocale())->toBe('ar');
});

test('an invalid locale falls back to the configured default', function (): void {
    $this->withSession(['locale' => 'fr'])
        ->get('/')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false);

    expect(app()->getLocale())->toBe('en');
});

test('a valid locale query parameter persists for the session', function (): void {
    $response = $this->get('/?locale=ar');

    $response->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);

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

test('English and Arabic translation dictionaries have matching keys', function (): void {
    $sortedKeys = static function (array $messages): array {
        $keys = array_keys($messages);
        sort($keys);

        return $keys;
    };

    $englishApp = Arr::dot(require lang_path('en/app.php'));
    $arabicApp = Arr::dot(require lang_path('ar/app.php'));
    $englishPlatform = Arr::dot(require lang_path('en/platform.php'));
    $arabicPlatform = Arr::dot(require lang_path('ar/platform.php'));
    $englishJson = json_decode(file_get_contents(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $arabicJson = json_decode(file_get_contents(lang_path('ar.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($sortedKeys($arabicApp))->toBe($sortedKeys($englishApp))
        ->and($sortedKeys($arabicPlatform))->toBe($sortedKeys($englishPlatform))
        ->and($sortedKeys($arabicJson))->toBe($sortedKeys($englishJson));
});

test('Arabic staff and workspace-module labels are translated', function (): void {
    app()->setLocale('ar');

    expect(__('app.staff_ui.search'))->toBe('بحث')
        ->and(__('app.staff_ui.search_placeholder'))->toBe('الاسم أو البريد الإلكتروني أو الهاتف أو المسمى الوظيفي')
        ->and(__('app.staff_ui.all_statuses'))->toBe('كل الحالات')
        ->and(__('app.staff_ui.reset'))->toBe('إعادة تعيين')
        ->and(__('Workspace modules'))->toBe('وحدات مساحة العمل')
        ->and(__('Control which modules are enabled for this workspace.'))->toBe('حدّد الوحدات المفعّلة لمساحة العمل هذه.')
        ->and(__('User security and platform access control.'))->toBe('أمان المستخدم والتحكم في صلاحيات الوصول إلى المنصة.');
});
