<?php

declare(strict_types=1);

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->assertRedirect(route('onboarding.business.create'))
        ->assertSessionHasNoErrors();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->id);
});

test('verified tenant member with incomplete onboarding is redirected to services setup after sign in', function (): void {
    $user = User::factory()->create([
        'email' => 'tenant-login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $tenant = Tenant::query()->create([
        'slug' => 'tenant-login',
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

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ])
        ->assertRedirect(route('services.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasNoErrors();
});

test('verified completed tenant member without subscription is redirected to billing after sign in', function (): void {
    $user = User::factory()->create([
        'email' => 'completed-tenant-login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $tenant = Tenant::query()->create([
        'slug' => 'completed-tenant-login',
        'status' => TenantStatus::Active,
        'settings' => [
            'onboarding' => [
                'completed' => true,
            ],
        ],
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ])
        ->assertRedirect(route('billing.subscription', ['tenant' => $tenant->slug]))
        ->assertSessionHasNoErrors();
});

test('unfinished onboarding can open the tenant dashboard directly', function (): void {
    $user = User::factory()->create();

    $tenant = Tenant::query()->create([
        'slug' => 'unfinished-dashboard',
        'status' => TenantStatus::Active,
        'settings' => [
            'onboarding' => [
                'completed' => false,
                'step' => 'services',
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
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee(__('app.dashboard'));
});

test('active platform admin is redirected to the platform dashboard after sign in', function (): void {
    $user = User::factory()->create([
        'email' => 'admin-login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    PlatformAdmin::query()->create([
        'user_id' => $user->getKey(),
        'is_active' => true,
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ])
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHasNoErrors();
});

test('invalid credentials do not authenticate the user', function (): void {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $this->from(route('login'))->post(route('login'), [
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