<?php

use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\ServiceStaff;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Service\Actions\AssignServiceToStaff;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
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

function operationsTenant(string $slug): Tenant
{
    return Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);
}

function operationsUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    return $user;
}

test('services and staff are isolated by tenant', function (): void {
    $tenantA = operationsTenant('alpha');
    $tenantB = operationsTenant('beta');

    app(CurrentTenant::class)->set($tenantA);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Haircut', 'ar' => 'حلاقة'],
        'price_minor' => 15000,
        'duration_minutes' => 30,
        'buffer_minutes' => 10,
    ]);

    $staffUser = operationsUser($tenantA, 'staff-a@example.com');
    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff');

    expect($service->tenant_id)->toBe($tenantA->id)
        ->and($staff->tenant_id)->toBe($tenantA->id)
        ->and($service->fresh()->is($service))->toBeTrue();

    app(CurrentTenant::class)->set($tenantB);

    expect(Service::query()->count())->toBe(0)
        ->and(StaffProfile::query()->count())->toBe(0);
});

test('staff roles are tenant scoped', function (): void {
    $tenantA = operationsTenant('alpha');
    $tenantB = operationsTenant('beta');
    $user = User::factory()->create();

    app(CurrentTenant::class)->set($tenantA);
    TenantMembership::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);
    $staffA = app(AddStaffMember::class)->handle($user, 'manager');

    app(CurrentTenant::class)->set($tenantB);
    $staffB = app(AddStaffMember::class)->handle($user, 'receptionist');

    setPermissionsTeamId($tenantA->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->hasRole('manager'))->toBeTrue()
        ->and($user->hasRole('receptionist'))->toBeFalse();

    setPermissionsTeamId($tenantB->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    expect($user->hasRole('receptionist'))->toBeTrue()
        ->and($user->hasRole('manager'))->toBeFalse();

    expect($staffA->tenant_id)->toBe($tenantA->id)
        ->and($staffB->tenant_id)->toBe($tenantB->id);
});

test('service can only be assigned to staff from the same tenant', function (): void {
    $tenantA = operationsTenant('alpha');
    $tenantB = operationsTenant('beta');

    app(CurrentTenant::class)->set($tenantA);
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 25000,
        'duration_minutes' => 45,
    ]);
    $userA = operationsUser($tenantA, 'a@example.com');
    $staffA = app(AddStaffMember::class)->handle($userA);

    app(CurrentTenant::class)->set($tenantB);
    $userB = operationsUser($tenantB, 'b@example.com');
    $staffB = app(AddStaffMember::class)->handle($userB);

    app(CurrentTenant::class)->set($tenantA);
    $assignment = app(AssignServiceToStaff::class)->handle($service, $staffA);

    expect($assignment->tenant_id)->toBe($tenantA->id)
        ->and(ServiceStaff::query()->count())->toBe(1);

    expect(fn () => app(AssignServiceToStaff::class)->handle($service, $staffB))
        ->toThrow(LogicException::class);
});

test('database rejects cross tenant service staff rows', function (): void {
    $tenantA = operationsTenant('alpha');
    $tenantB = operationsTenant('beta');

    app(CurrentTenant::class)->set($tenantA);
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Test'],
        'price_minor' => 1000,
        'duration_minutes' => 15,
    ]);
    $userA = operationsUser($tenantA, 'staff-a2@example.com');
    $staffA = app(AddStaffMember::class)->handle($userA);

    app(CurrentTenant::class)->set($tenantB);
    $userB = operationsUser($tenantB, 'staff-b2@example.com');
    $staffB = app(AddStaffMember::class)->handle($userB);

    expect(fn () => DB::table('service_staff')->insert([
        'tenant_id' => $tenantB->id,
        'service_id' => $service->id,
        'staff_id' => $staffA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('service_staff')->insert([
        'tenant_id' => $tenantA->id,
        'service_id' => $service->id,
        'staff_id' => $staffB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('business and staff working hours are tenant scoped', function (): void {
    $tenantA = operationsTenant('alpha');
    $tenantB = operationsTenant('beta');

    app(CurrentTenant::class)->set($tenantA);
    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ['day_of_week' => DayOfWeek::Tuesday->value, 'is_closed' => true],
    ]);

    $user = operationsUser($tenantA, 'schedule@example.com');
    $staff = app(AddStaffMember::class)->handle($user);
    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '10:00', 'closes_at' => '18:00'],
    ]);

    StaffDayOff::query()->create([
        'staff_id' => $staff->id,
        'starts_on' => '2026-10-01',
        'ends_on' => '2026-10-02',
        'reason' => 'Leave',
    ]);

    expect(BusinessWorkingHour::query()->count())->toBe(2)
        ->and($staff->workingHours()->count())->toBe(1)
        ->and($staff->daysOff()->count())->toBe(1);

    app(CurrentTenant::class)->set($tenantB);

    expect(BusinessWorkingHour::query()->count())->toBe(0)
        ->and(StaffProfile::query()->count())->toBe(0);
});
