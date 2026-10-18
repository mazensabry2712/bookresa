<?php

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login page uses the application login view', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Welcome back')
        ->assertSee('Account access')
        ->assertSee('Sign in to manage your business workspace.')
        ->assertSee('data-bookresa-password-toggle', false)
        ->assertSee('logo.png')
        ->assertDontSee('Run bookings, customers and your workspace from one place.');
});

test('registration page uses the application registration view', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee('Create your account first.');
});

test('guest auth pages use the dedicated BookResa session cookie', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertCookie(config('session.cookie'));
});

test('guest auth pages are not cached with stale csrf tokens', function (): void {
    foreach ([route('login'), route('register'), route('password.request')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', '0');
    }
});

test('authenticated users without a workspace are redirected safely from guest auth pages', function (): void {
    $user = User::factory()->create();

    foreach ([route('login'), route('register')] as $url) {
        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('onboarding.business.create'));
    }
});

test('authenticated tenant members are redirected to their workspace when opening guest auth pages', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::query()->create([
        'slug' => 'auth-redirect-workspace',
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

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('dashboard', ['tenant' => $tenant->slug]));
});

test('active platform admins are redirected to the platform dashboard when opening guest auth pages', function (): void {
    $user = User::factory()->create();

    PlatformAdmin::query()->create([
        'user_id' => $user->getKey(),
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('register'))
        ->assertRedirect(route('admin.dashboard'));
});

test('authenticated unverified users are redirected to email verification from guest auth pages', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('verification.notice'));
});


test('email verification page asks for a six-digit one-time code', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('autocomplete="one-time-code"', false)
        ->assertSee(route('verification.otp.verify'), false)
        ->assertSee(route('verification.otp.resend'), false)
        ->assertSee(__('app.verify_email_code_label'));
});
