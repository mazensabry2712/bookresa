<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('authenticated users can log out and the session is no longer authenticated', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});

test('authenticated users can update their password with the correct current password', function (): void {
    $user = User::factory()->create([
        'password' => 'old-password',
    ]);

    $this->actingAs($user)
        ->from(route('onboarding.business.create'))
        ->put(route('user-password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ])
        ->assertRedirect(route('onboarding.business.create'))
        ->assertSessionHas('status', 'password-updated')
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-password-12345', $user->fresh()->password))->toBeTrue();
});

test('password update rejects an incorrect current password', function (): void {
    $user = User::factory()->create([
        'password' => 'old-password',
    ]);

    $this->actingAs($user)
        ->from(route('onboarding.business.create'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-12345',
            'password_confirmation' => 'new-password-12345',
        ])
        ->assertRedirect(route('onboarding.business.create'))
        ->assertSessionHasErrorsIn('updatePassword', 'current_password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

test('two factor challenge cannot be opened without a pending two factor login', function (): void {
    $this->get(route('two-factor.login'))
        ->assertRedirect(route('login'));
});

test('two factor authentication remains enabled with password confirmation required', function (): void {
    expect(config('fortify.features'))
        ->toContain('two-factor-authentication')
        ->and(config('fortify-options.two-factor-authentication.confirmPassword'))->toBeTrue();
});
