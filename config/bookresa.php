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

    'booking' => [
        'slot_interval_minutes' => (int) env('BOOKRESA_BOOKING_SLOT_INTERVAL', 15),
    ],

    'payments' => [
        'default_provider' => env('BOOKRESA_PAYMENT_PROVIDER', 'kashier'),
        'kashier' => [
            'mode' => env('KASHIER_MODE', 'test'),
            'base_url' => env('KASHIER_BASE_URL', 'https://test-api.kashier.io'),
            'merchant_id' => env('KASHIER_MERCHANT_ID'),
            'api_key' => env('KASHIER_API_KEY'),
            'secret_key' => env('KASHIER_SECRET_KEY'),
            'merchant_redirect' => env('KASHIER_MERCHANT_REDIRECT'),
            'server_webhook' => env('KASHIER_SERVER_WEBHOOK'),
            'max_failure_attempts' => (int) env('KASHIER_MAX_FAILURE_ATTEMPTS', 3),
            'allowed_methods' => env('KASHIER_ALLOWED_METHODS', 'card,wallet'),
            'display' => env('KASHIER_DISPLAY', 'en'),
            'expire_minutes' => (int) env('KASHIER_EXPIRE_MINUTES', 30),
            'enable_3ds' => (bool) env('KASHIER_ENABLE_3DS', true),
        ],
    ],

    'rbac' => [
        'permissions' => [
            'business.view',
            'business.update',
            'services.view',
            'services.create',
            'services.update',
            'services.delete',
            'staff.view',
            'staff.manage',
            'customers.view',
            'customers.create',
            'customers.update',
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'bookings.cancel',
            'bookings.complete',
            'calendar.view',
            'billing.view',
            'subscription.manage',
            'settings.manage',
            'reports.view',
        ],

        'roles' => [
            'owner' => [
                'business.view',
                'business.update',
                'services.view',
                'services.create',
                'services.update',
                'services.delete',
                'staff.view',
                'staff.manage',
                'customers.view',
                'customers.create',
                'customers.update',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.cancel',
                'bookings.complete',
                'calendar.view',
                'billing.view',
                'subscription.manage',
                'settings.manage',
                'reports.view',
            ],
            'manager' => [
                'business.view',
                'business.update',
                'services.view',
                'services.create',
                'services.update',
                'services.delete',
                'staff.view',
                'staff.manage',
                'customers.view',
                'customers.create',
                'customers.update',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.cancel',
                'bookings.complete',
                'calendar.view',
                'settings.manage',
                'reports.view',
            ],
            'receptionist' => [
                'business.view',
                'customers.view',
                'customers.create',
                'customers.update',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.cancel',
                'calendar.view',
            ],
            'staff' => [
                'business.view',
                'bookings.view',
                'bookings.update',
                'bookings.complete',
                'calendar.view',
            ],
        ],
    ],

    'tenant' => [
        'model' => \App\Domain\Tenant\Models\Tenant::class,
        'context' => \App\Domain\Tenant\Services\CurrentTenant::class,
    ],
];
