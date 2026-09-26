<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function rbacWorkspace(string $name = 'RBAC Clinic'): array
{
    $owner = User::factory()->create([
        'name' => $name.' Owner',
    ]);

    $businessType = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $businessType, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$owner, $tenant];
}

function rbacMember($tenant, string $role, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($user, $role);

    return $user;
}

test('configured roles provision the exact configured permissions', function (): void {
    [$owner, $tenant] = rbacWorkspace();

    app(CurrentTenant::class)->set($tenant);
    $provisioner = app(TenantRoleProvisioner::class);

    foreach (array_keys(config('bookresa.rbac.roles')) as $roleKey) {
        $provisioner->provisionRole($tenant, $roleKey);
    }

    setPermissionsTeamId($tenant->id);

    foreach (config('bookresa.rbac.roles') as $roleKey => $expectedPermissions) {
        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', $roleKey)
            ->firstOrFail();

        expect($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expectedPermissions)->sort()->values()->all());
    }

    expect($owner->fresh()->tenantMemberships()->where('tenant_id', $tenant->id)->exists())
        ->toBeTrue();
});

test('tenant role provisioning rejects roles outside the configured role contract', function (): void {
    [, $tenant] = rbacWorkspace();

    expect(fn () => app(TenantRoleProvisioner::class)->provisionRole($tenant, 'super-owner'))
        ->toThrow(InvalidArgumentException::class);

    expect(Role::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'super-owner')
        ->exists())
        ->toBeFalse();
});

test('owner has billing access while manager does not', function (): void {
    [$owner, $tenant] = rbacWorkspace();
    $manager = rbacMember($tenant, 'manager', 'rbac-manager@example.com');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertOk();

    $this->actingAs($manager)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertForbidden();
});

test('manager can manage services and staff while receptionist cannot', function (): void {
    [, $tenant] = rbacWorkspace();
    $manager = rbacMember($tenant, 'manager', 'rbac-manager-services@example.com');
    $receptionist = rbacMember($tenant, 'receptionist', 'rbac-receptionist@example.com');

    $this->actingAs($manager)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk();

    $this->actingAs($manager)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index'))
        ->assertOk();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index'))
        ->assertForbidden();
});

test('staff is limited to its booking and calendar permissions', function (): void {
    [, $tenant] = rbacWorkspace();
    $staff = rbacMember($tenant, 'staff', 'rbac-staff@example.com');

    app(CurrentTenant::class)->set($tenant);
    setPermissionsTeamId($tenant->id);

    expect($staff->fresh()->can('bookings.view'))->toBeTrue()
        ->and($staff->fresh()->can('bookings.complete'))->toBeTrue()
        ->and($staff->fresh()->can('services.view'))->toBeFalse()
        ->and($staff->fresh()->can('staff.manage'))->toBeFalse()
        ->and($staff->fresh()->can('billing.view'))->toBeFalse()
        ->and($staff->fresh()->can('subscription.manage'))->toBeFalse();
});

test('a tenant role cannot be reused to grant access in another tenant', function (): void {
    [, $tenantA] = rbacWorkspace('Tenant A');
    [, $tenantB] = rbacWorkspace('Tenant B');
    $shared = User::factory()->create(['email' => 'rbac-shared@example.com']);

    app(CurrentTenant::class)->set($tenantA);
    app(TenantRoleProvisioner::class)->provisionRole($tenantA, 'manager');

    setPermissionsTeamId($tenantA->id);
    $shared->assignRole('manager');

    expect($shared->fresh()->hasRole('manager'))->toBeTrue();

    setPermissionsTeamId($tenantB->id);
    $shared->unsetRelation('roles')->unsetRelation('permissions');

    expect($shared->hasRole('manager'))->toBeFalse();
});

test('forged tenant session falls back to an authorized tenant instead of switching access', function (): void {
    [$ownerA, $tenantA] = rbacWorkspace('Authorized Tenant');
    [, $tenantB] = rbacWorkspace('Other Tenant');

    $this->actingAs($ownerA)
        ->withSession(['tenant_id' => $tenantB->id])
        ->get(route('business.profile.edit'))
        ->assertOk()
        ->assertSee('Authorized Tenant')
        ->assertDontSee('Other Tenant');

    expect($tenantA->id)->not->toBe($tenantB->id);
});

test('platform admin access stays separate from tenant roles', function (): void {
    [$owner, $tenant] = rbacWorkspace();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('admin.plans.index'))
        ->assertForbidden();
});
