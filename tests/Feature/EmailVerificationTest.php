<?php

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('registration redirects to a valid post-registration location', function (): void {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Verified Candidate',
        'email' => 'verified-candidate@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    expect(auth()->check())->toBeTrue();
});

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
        ->assertRedirect(route('onboarding.business.create').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});


test('verified tenant members are redirected to their workspace dashboard', function (): void {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $tenant = Tenant::query()->create([
        'slug' => 'verified-workspace',
        'status' => TenantStatus::Active,
        'settings' => [
            'onboarding' => [
                'completed' => false,
            ],
        ],
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

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
        ->assertRedirect(route('dashboard', ['tenant' => $tenant->slug, 'verified' => 1]));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('unverified users can request another verification email', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->withHeader('Referer', route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification ignores a stale intended dashboard destination for a new account', function (): void {
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
        ->withSession(['url.intended' => '/dashboard'])
        ->get($url)
        ->assertRedirect(route('onboarding.business.create').'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
