<?php

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function makeTenantForUser(User $user, string $slug, bool $primary = false): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => $primary,
    ]);

    return $tenant;
}

test('roles are isolated by tenant team context', function (): void {
    $user = User::factory()->create();
    $tenantA = makeTenantForUser($user, 'tenant-a', true);
    $tenantB = makeTenantForUser($user, 'tenant-b');

    Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
        'tenant_id' => $tenantA->id,
    ]);

    Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
        'tenant_id' => $tenantB->id,
    ]);

    setPermissionsTeamId($tenantA->id);
    $user->assignRole('manager');

    expect($user->hasRole('manager'))->toBeTrue();

    setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    expect($user->hasRole('manager'))->toBeFalse();
});

test('tenant middleware resolves the primary membership when no tenant is selected', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-primary', function () {
        return response()->json([
            'tenant_id' => app(CurrentTenant::class)->id(),
        ]);
    });

    $user = User::factory()->create();
    $tenantA = makeTenantForUser($user, 'tenant-a', false);
    $tenantB = makeTenantForUser($user, 'tenant-b', true);

    $this->actingAs($user)
        ->get('/__test/tenant-primary')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $tenantB->id,
        ]);
});

test('tenant middleware resolves the selected membership and permission team', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-context', function () {
        return response()->json([
            'tenant_id' => app(CurrentTenant::class)->id(),
            'permission_team_id' => getPermissionsTeamId(),
        ]);
    });

    $user = User::factory()->create();
    $tenantA = makeTenantForUser($user, 'tenant-a', true);
    $tenantB = makeTenantForUser($user, 'tenant-b');

    $response = $this->actingAs($user)
        ->withSession(['tenant_id' => $tenantB->id])
        ->get('/__test/tenant-context');

    $response->assertOk()->assertJson([
        'tenant_id' => $tenantB->id,
        'permission_team_id' => $tenantB->id,
    ]);

    expect($response->headers->get('content-type'))->toContain('application/json');
});

test('tenant middleware falls back to primary membership when the selected tenant is unavailable', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-fallback', function () {
        return response()->json([
            'tenant_id' => app(CurrentTenant::class)->id(),
        ]);
    });

    $user = User::factory()->create();
    $tenantA = makeTenantForUser($user, 'tenant-a', true);
    makeTenantForUser($user, 'tenant-b');

    $response = $this->actingAs($user)
        ->withSession(['tenant_id' => 999999])
        ->get('/__test/tenant-fallback');

    $response->assertOk()->assertJson([
        'tenant_id' => $tenantA->id,
    ]);
});

test('tenant middleware falls back when the selected tenant is inactive', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-inactive-fallback', function () {
        return response()->json([
            'tenant_id' => app(CurrentTenant::class)->id(),
        ]);
    });

    $user = User::factory()->create();
    makeTenantForUser($user, 'tenant-inactive', true)->update([
        'status' => TenantStatus::Suspended,
    ]);
    $activeTenant = makeTenantForUser($user, 'tenant-active');

    $this->actingAs($user)
        ->withSession(['tenant_id' => Tenant::query()->where('slug', 'tenant-inactive')->value('id')])
        ->get('/__test/tenant-inactive-fallback')
        ->assertOk()
        ->assertJson([
            'tenant_id' => $activeTenant->id,
        ]);
});

test('tenant middleware rejects authenticated users without an active membership', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-forbidden', fn () => 'ok');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/__test/tenant-forbidden')
        ->assertForbidden();
});

test('tenant context is cleared after the request completes', function (): void {
    Route::middleware(['web', 'auth', 'tenant'])->get('/__test/tenant-cleanup', function () {
        return response()->json([
            'tenant_id' => app(CurrentTenant::class)->id(),
            'permission_team_id' => getPermissionsTeamId(),
        ]);
    });

    $user = User::factory()->create();
    $tenant = makeTenantForUser($user, 'tenant-a', true);

    $this->actingAs($user)
        ->get('/__test/tenant-cleanup')
        ->assertOk();

    expect(app(CurrentTenant::class)->id())->toBeNull()
        ->and(getPermissionsTeamId())->toBeNull()
        ->and($user->relationLoaded('roles'))->toBeFalse()
        ->and($user->relationLoaded('permissions'))->toBeFalse();
});
