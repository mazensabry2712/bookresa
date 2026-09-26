<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;

test('unverified users are redirected to the verification notice before onboarding', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('onboarding.business.create'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users can access the verification-protected onboarding route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding.business.create'))
        ->assertOk();
});

test('signed verification link marks the user verified', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(30),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

test('unverified users can request another verification email', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user);
});
