<?php

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Platform\Models\PlatformSetting;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

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

test('platform admin can save live Kashier credentials encrypted and secrets stay hidden from the settings page', function (): void {
    $admin = User::factory()->create(['email' => 'kashier-integration-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.settings.kashier.update'), [
            'kashier_merchant_id' => 'MID-LIVE-BOOKRESA',
            'kashier_api_key' => 'live-payment-api-key-value',
            'kashier_secret_key' => 'live-secret-key-value',
        ])
        ->assertRedirect(route('admin.settings.index'));

    $apiKeyCiphertext = PlatformSetting::query()->where('key', 'kashier_api_key_encrypted')->value('value');
    $secretKeyCiphertext = PlatformSetting::query()->where('key', 'kashier_secret_key_encrypted')->value('value');

    expect($apiKeyCiphertext)->not->toBe('live-payment-api-key-value')
        ->and($secretKeyCiphertext)->not->toBe('live-secret-key-value')
        ->and(Crypt::decryptString((string) $apiKeyCiphertext))->toBe('live-payment-api-key-value')
        ->and(Crypt::decryptString((string) $secretKeyCiphertext))->toBe('live-secret-key-value')
        ->and(app(PlatformSettings::class)->kashierConfig())->toMatchArray([
            'mode' => 'live',
            'base_url' => 'https://api.kashier.io',
            'merchant_id' => 'MID-LIVE-BOOKRESA',
            'api_key' => 'live-payment-api-key-value',
            'secret_key' => 'live-secret-key-value',
            'server_webhook' => route('webhooks.kashier'),
            'allowed_methods' => 'card,wallet',
        ]);

    $this->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('MID-LIVE-BOOKRESA')
        ->assertSee('Payment API Key saved')
        ->assertSee('Secret Key saved')
        ->assertDontSee('live-payment-api-key-value')
        ->assertDontSee('live-secret-key-value');
});

test('platform admin can enable centralized booking payments after live credentials are stored in Super Admin', function (): void {
    $admin = User::factory()->create(['email' => 'central-live-payments-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    app(PlatformSettings::class)->saveKashierCredentials([
        'merchant_id' => 'MID-LIVE-BOOKRESA',
        'api_key' => 'live-payment-api-key-value',
        'secret_key' => 'live-secret-key-value',
        'server_webhook' => 'https://bookresa.example/webhooks/kashier',
    ]);

    $this->actingAs($admin)
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
        ->assertRedirect();

    expect(PlatformSetting::query()->where('key', 'centralized_booking_payments_enabled')->value('value'))->toBe('1');
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

    $this->actingAs($user)
        ->put(route('admin.settings.kashier.update'), [
            'kashier_merchant_id' => 'MID-UNAUTHORIZED',
            'kashier_api_key' => 'should-not-save',
            'kashier_secret_key' => 'should-not-save',
        ])
        ->assertForbidden();
});
