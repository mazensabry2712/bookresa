<?php

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Platform\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('platform admin can view and update platform settings', function (): void {
    $admin = User::factory()->create(['email' => 'settings-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    PlatformSetting::query()->create([
        'key' => 'platform_name',
        'value' => 'Old BookResa',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Old BookResa');

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'platform_name' => 'BookResa Pro',
            'support_email' => 'support@example.com',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Cairo',
            'booking_slot_interval_minutes' => 30,
            'centralized_booking_payments_enabled' => '0',
            'commission_free_customer_limit' => 1000,
            'booking_commission_rate_percent' => 5,
        ])
        ->assertRedirect();

    expect(PlatformSetting::query()->pluck('value', 'key')->all())
        ->toMatchArray([
            'platform_name' => 'BookResa Pro',
            'support_email' => 'support@example.com',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Cairo',
            'booking_slot_interval_minutes' => '30',
            'centralized_booking_payments_enabled' => '0',
            'commission_free_customer_limit' => '1000',
            'booking_commission_rate_basis_points' => '500',
        ]);
});

test('platform settings validation rejects invalid values', function (): void {
    $admin = User::factory()->create(['email' => 'settings-validation@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.settings.index'))
        ->put(route('admin.settings.update'), [
            'platform_name' => '',
            'support_email' => 'invalid',
            'default_locale' => 'fr',
            'default_timezone' => 'invalid/timezone',
            'booking_slot_interval_minutes' => 2,
            'centralized_booking_payments_enabled' => '0',
            'commission_free_customer_limit' => -1,
            'booking_commission_rate_percent' => 101,
        ])
        ->assertSessionHasErrors([
            'platform_name',
            'support_email',
            'default_locale',
            'default_timezone',
            'booking_slot_interval_minutes',
            'commission_free_customer_limit',
            'booking_commission_rate_percent',
        ]);
});

test('platform admin cannot enable centralized payments without platform credentials and webhook', function (): void {
    $admin = User::factory()->create(['email' => 'central-payments-setup@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    config([
        'bookresa.payments.kashier.merchant_id' => '',
        'bookresa.payments.kashier.api_key' => '',
        'bookresa.payments.kashier.secret_key' => '',
        'bookresa.payments.kashier.server_webhook' => '',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.settings.index'))
        ->put(route('admin.settings.update'), [
            'platform_name' => 'BookResa',
            'support_email' => 'support@example.com',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Cairo',
            'booking_slot_interval_minutes' => 15,
            'centralized_booking_payments_enabled' => '1',
            'commission_free_customer_limit' => 1000,
            'booking_commission_rate_percent' => 5,
        ])
        ->assertSessionHasErrors('centralized_booking_payments_enabled');

    expect(PlatformSetting::query()->where('key', 'centralized_booking_payments_enabled')->value('value'))->toBeNull();
});

test('non platform admin cannot manage platform settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});
