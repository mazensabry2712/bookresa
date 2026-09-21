<?php

return [
    'name' => env('BOOKRESA_NAME', 'BookResa'),

    'locales' => [
        'en',
        'ar',
    ],

    'default_locale' => env('BOOKRESA_DEFAULT_LOCALE', 'en'),

    'fallback_locale' => env('BOOKRESA_FALLBACK_LOCALE', 'en'),

    'themes' => [
        'light',
        'dark',
    ],

    'default_theme' => env('BOOKRESA_DEFAULT_THEME', 'system'),

    'tenant' => [
        'model' => \App\Domain\Tenant\Models\Tenant::class,
        'context' => \App\Domain\Tenant\Services\CurrentTenant::class,
    ],
];
