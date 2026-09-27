<?php

use AppModelsUser;
use IlluminateFoundationTestingRefreshDatabase;

uses(RefreshDatabase::class);

test('verified user can sign in with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $this->post(route('login'), [
        'email' => 'login@example.com',
        'password' => 'secret-password',
    ])
        ->assertRedirect('/')
        ->assertSessionHasNoErrors();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

test('invalid credentials do not authenticate the user', function (): void {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $this->post(route('login'), [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

test('an authenticated unverified user is blocked from onboarding until email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('onboarding.business.create'))
        ->assertRedirect(route('verification.notice'));
});
