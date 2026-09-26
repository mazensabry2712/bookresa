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
        ])
        ->assertRedirect();

    expect(PlatformSetting::query()->pluck('value', 'key')->all())
        ->toMatchArray([
            'platform_name' => 'BookResa Pro',
            'support_email' => 'support@example.com',
            'default_locale' => 'en',
            'default_timezone' => 'Africa/Cairo',
            'booking_slot_interval_minutes' => '30',
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
        ])
        ->assertSessionHasErrors([
            'platform_name',
            'support_email',
            'default_locale',
            'default_timezone',
            'booking_slot_interval_minutes',
        ]);
});

test('non platform admin cannot manage platform settings', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});
