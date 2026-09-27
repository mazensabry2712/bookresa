<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('registered users can request a password reset link', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', trans('app.password_reset_link_sent'));

    Notification::assertSentTo($user, ResetPassword::class);
});

test('users can reset their password with a valid reset token', function (): void {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'password' => 'old-password',
    ]);

    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-12345',
        'password_confirmation' => 'new-password-12345',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', trans('passwords.reset'));

    expect(Hash::check('new-password-12345', $user->fresh()->password))->toBeTrue();
});

test('unknown password reset email does not reveal account existence', function (): void {
    Notification::fake();

    $this->post(route('password.email'), [
        'email' => 'missing@example.com',
    ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', trans('app.password_reset_link_sent'))
        ->assertSessionMissing('errors');

    Notification::assertNothingSent();
});
