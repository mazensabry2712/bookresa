<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Actions\AddBusinessBreak;
use App\Domain\Scheduling\Actions\AddStaffAvailability;
use App\Domain\Scheduling\Actions\AddStaffDayOff;
use App\Domain\Scheduling\Actions\RemoveBusinessBreak;
use App\Domain\Scheduling\Actions\RemoveBusinessHoliday;
use App\Domain\Scheduling\Actions\RemoveSpecialWorkingHour;
use App\Domain\Scheduling\Actions\RemoveStaffAvailability;
use App\Domain\Scheduling\Actions\RemoveStaffDayOff;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Actions\UpsertBusinessHoliday;
use App\Domain\Scheduling\Actions\UpsertSpecialWorkingHour;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Actions\AddStaffMember;
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
use LogicException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function backendSchedulingTenant(string $slug): Tenant
{
    return Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);
}

function backendSchedulingStaff(Tenant $tenant, string $email): StaffProfile
{
    app(CurrentTenant::class)->set($tenant);

    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->firstOrCreate([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
    ], [
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    return app(AddStaffMember::class)->handle($user, 'staff');
}

test('business scheduling actions persist regular hours breaks holidays and overrides per tenant', function (): void {
    $tenant = backendSchedulingTenant('scheduling-alpha');

    app(CurrentTenant::class)->set($tenant);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ['day_of_week' => DayOfWeek::Sunday->value, 'is_closed' => true],
    ]);

    $break = app(AddBusinessBreak::class)->handle([
        'day_of_week' => DayOfWeek::Monday->value,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
        'label' => 'Lunch',
    ]);

    $holiday = app(UpsertBusinessHoliday::class)->handle([
        'holiday_date' => '2026-10-06',
        'reason' => 'Public holiday',
    ]);

    $special = app(UpsertSpecialWorkingHour::class)->handle([
        'work_date' => '2026-10-07',
        'opens_at' => '10:00',
        'closes_at' => '15:00',
        'reason' => 'Short day',
    ]);

    expect(BusinessWorkingHour::query()->count())->toBe(2)
        ->and($break->tenant_id)->toBe($tenant->id)
        ->and($holiday->tenant_id)->toBe($tenant->id)
        ->and($special->tenant_id)->toBe($tenant->id)
        ->and(BusinessBreak::query()->count())->toBe(1)
        ->and(BusinessHoliday::query()->count())->toBe(1)
        ->and(SpecialWorkingHour::query()->count())->toBe(1);
});

test('business holiday and special hours are upserted by date', function (): void {
    $tenant = backendSchedulingTenant('scheduling-upsert');

    app(CurrentTenant::class)->set($tenant);

    app(UpsertBusinessHoliday::class)->handle([
        'holiday_date' => '2026-10-06',
        'reason' => 'Initial',
    ]);

    $holiday = app(UpsertBusinessHoliday::class)->handle([
        'holiday_date' => '2026-10-06',
        'reason' => 'Updated',
    ]);

    app(UpsertSpecialWorkingHour::class)->handle([
        'work_date' => '2026-10-07',
        'opens_at' => '10:00',
        'closes_at' => '14:00',
        'reason' => 'Initial',
    ]);

    $special = app(UpsertSpecialWorkingHour::class)->handle([
        'work_date' => '2026-10-07',
        'is_closed' => true,
        'reason' => 'Closed',
    ]);

    expect(BusinessHoliday::query()->count())->toBe(1)
        ->and($holiday->fresh()->reason)->toBe('Updated')
        ->and(SpecialWorkingHour::query()->count())->toBe(1)
        ->and($special->fresh()->is_closed)->toBeTrue()
        ->and($special->fresh()->opens_at)->toBeNull()
        ->and($special->fresh()->closes_at)->toBeNull()
        ->and($special->fresh()->reason)->toBe('Closed');
});

test('staff scheduling actions persist working hours day offs and explicit availability', function (): void {
    $tenant = backendSchedulingTenant('scheduling-staff');
    $staff = backendSchedulingStaff($tenant, 'backend-schedule-staff@example.com');

    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ['day_of_week' => DayOfWeek::Tuesday->value, 'opens_at' => '10:00', 'closes_at' => '18:00'],
    ]);

    $dayOff = app(AddStaffDayOff::class)->handle($staff, [
        'starts_on' => '2026-10-10',
        'ends_on' => '2026-10-12',
        'reason' => 'Leave',
    ]);

    $availability = app(AddStaffAvailability::class)->handle($staff, [
        'available_date' => '2026-10-17',
        'starts_at' => '10:00',
        'ends_at' => '13:00',
    ]);

    expect(StaffWorkingHour::query()->count())->toBe(2)
        ->and($dayOff->staff_id)->toBe($staff->id)
        ->and($availability->staff_id)->toBe($staff->id)
        ->and(StaffDayOff::query()->count())->toBe(1)
        ->and(StaffAvailability::query()->count())->toBe(1);
});

test('staff schedule mutation actions reject another tenant', function (): void {
    $tenantA = backendSchedulingTenant('scheduling-a');
    $tenantB = backendSchedulingTenant('scheduling-b');
    $staffA = backendSchedulingStaff($tenantA, 'tenant-a-schedule@example.com');

    app(CurrentTenant::class)->set($tenantA);

    $breakA = app(AddBusinessBreak::class)->handle([
        'day_of_week' => DayOfWeek::Monday->value,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
    ]);
    $holidayA = app(UpsertBusinessHoliday::class)->handle([
        'holiday_date' => '2026-11-01',
        'reason' => 'Tenant A',
    ]);
    $specialA = app(UpsertSpecialWorkingHour::class)->handle([
        'work_date' => '2026-11-02',
        'opens_at' => '09:00',
        'closes_at' => '12:00',
    ]);
    $dayOffA = app(AddStaffDayOff::class)->handle($staffA, [
        'starts_on' => '2026-11-03',
        'ends_on' => '2026-11-03',
    ]);
    $availabilityA = app(AddStaffAvailability::class)->handle($staffA, [
        'available_date' => '2026-11-04',
        'starts_at' => '09:00',
        'ends_at' => '12:00',
    ]);

    app(CurrentTenant::class)->set($tenantB);

    expect(fn () => app(RemoveBusinessBreak::class)->handle($breakA))
        ->toThrow(LogicException::class);

    expect(fn () => app(RemoveBusinessHoliday::class)->handle($holidayA))
        ->toThrow(LogicException::class);

    expect(fn () => app(RemoveSpecialWorkingHour::class)->handle($specialA))
        ->toThrow(LogicException::class);

    expect(fn () => app(RemoveStaffDayOff::class)->handle($dayOffA))
        ->toThrow(LogicException::class);

    expect(fn () => app(RemoveStaffAvailability::class)->handle($availabilityA))
        ->toThrow(LogicException::class);

    expect(BusinessBreak::withoutGlobalScopes()->whereKey($breakA->id)->exists())->toBeTrue()
        ->and(BusinessHoliday::withoutGlobalScopes()->whereKey($holidayA->id)->exists())->toBeTrue()
        ->and(SpecialWorkingHour::withoutGlobalScopes()->whereKey($specialA->id)->exists())->toBeTrue()
        ->and(StaffDayOff::withoutGlobalScopes()->whereKey($dayOffA->id)->exists())->toBeTrue()
        ->and(StaffAvailability::withoutGlobalScopes()->whereKey($availabilityA->id)->exists())->toBeTrue();
});

test('scheduling query scopes isolate tenants', function (): void {
    $tenantA = backendSchedulingTenant('scheduling-query-a');
    $tenantB = backendSchedulingTenant('scheduling-query-b');

    app(CurrentTenant::class)->set($tenantA);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    backendSchedulingStaff($tenantA, 'query-a@example.com');

    app(CurrentTenant::class)->set($tenantB);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Tuesday->value, 'opens_at' => '10:00', 'closes_at' => '18:00'],
    ]);

    expect(BusinessWorkingHour::query()->count())->toBe(1)
        ->and(StaffProfile::query()->count())->toBe(0);

    app(CurrentTenant::class)->set($tenantA);

    expect(BusinessWorkingHour::query()->count())->toBe(1)
        ->and(StaffProfile::query()->count())->toBe(1);
});
