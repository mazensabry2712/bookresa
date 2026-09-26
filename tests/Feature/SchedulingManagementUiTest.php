<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

function schedulingWorkspace(string $ownerName = 'Scheduling Owner'): array
{
    $owner = User::factory()->create(['name' => $ownerName]);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Scheduling Clinic',
        'name_en' => 'Scheduling Clinic',
        'name_ar' => 'عيادة المواعيد',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$owner, $tenant];
}

function schedulingStaff(Tenant $tenant, string $email): array
{
    app(CurrentTenant::class)->set($tenant);

    $user = User::factory()->create(['email' => $email]);
    $staff = app(AddStaffMember::class)->handle($user, 'staff', [
        'display_name' => 'Schedule Staff',
    ]);

    return [$user, $staff];
}

test('owner can manage business scheduling rules', function (): void {
    [$owner, $tenant] = schedulingWorkspace();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index'))
        ->assertOk()
        ->assertSee('Business working hours')
        ->assertSee('Recurring breaks')
        ->assertSee('Business holidays')
        ->assertSee('Special working hours');

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update'), [
            'hours' => [
                ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 2, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 3, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 4, 'opens_at' => '10:00', 'closes_at' => '18:00'],
                ['day_of_week' => 5, 'opens_at' => '10:00', 'closes_at' => '18:00'],
                ['day_of_week' => 6, 'opens_at' => '10:00', 'closes_at' => '14:00'],
                ['day_of_week' => 7, 'opens_at' => '00:00', 'closes_at' => '00:00', 'is_closed' => true],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.breaks.store'), [
            'day_of_week' => 1,
            'starts_at' => '13:00',
            'ends_at' => '14:00',
            'label' => 'Lunch',
        ])
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.holidays.store'), [
            'holiday_date' => '2026-10-06',
            'reason' => 'Public holiday',
        ])
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.special-hours.store'), [
            'work_date' => '2026-10-07',
            'opens_at' => '11:00',
            'closes_at' => '15:00',
            'reason' => 'Short day',
        ])
        ->assertRedirect();

    app(CurrentTenant::class)->set($tenant);

    $break = BusinessBreak::query()->firstOrFail();
    $holiday = BusinessHoliday::query()->firstOrFail();
    $special = SpecialWorkingHour::query()->firstOrFail();

    expect(BusinessWorkingHour::query()->count())->toBe(7)
        ->and(BusinessBreak::query()->count())->toBe(1)
        ->and(BusinessHoliday::query()->count())->toBe(1)
        ->and(SpecialWorkingHour::query()->count())->toBe(1);

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.breaks.destroy', $break))
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.holidays.destroy', $holiday))
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.special-hours.destroy', $special))
        ->assertRedirect();

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessBreak::query()->count())->toBe(0)
        ->and(BusinessHoliday::query()->count())->toBe(0)
        ->and(SpecialWorkingHour::query()->count())->toBe(0);
});

test('owner can manage staff schedule, day off and explicit availability', function (): void {
    [$owner, $tenant] = schedulingWorkspace();
    [, $staff] = schedulingStaff($tenant, 'schedule-staff@example.com');

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.staff.hours.update', $staff), [
            'hours' => [
                ['day_of_week' => 1, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 2, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 3, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 4, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 5, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                ['day_of_week' => 6, 'opens_at' => '10:00', 'closes_at' => '14:00'],
                ['day_of_week' => 7, 'opens_at' => '00:00', 'closes_at' => '00:00', 'is_closed' => true],
            ],
        ])
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff.days-off.store', $staff), [
            'starts_on' => '2026-10-10',
            'ends_on' => '2026-10-12',
            'reason' => 'Leave',
        ])
        ->assertRedirect();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff.availability.store', $staff), [
            'available_date' => '2026-10-17',
            'starts_at' => '10:00',
            'ends_at' => '13:00',
        ])
        ->assertRedirect();

    app(CurrentTenant::class)->set($tenant);

    expect(StaffWorkingHour::query()->where('staff_id', $staff->id)->count())->toBe(7)
        ->and(StaffDayOff::query()->where('staff_id', $staff->id)->count())->toBe(1)
        ->and(StaffAvailability::query()->where('staff_id', $staff->id)->count())->toBe(1);
});

test('receptionist cannot manage scheduling', function (): void {
    [$owner, $tenant] = schedulingWorkspace();
    $receptionist = User::factory()->create(['email' => 'receptionist-schedule@example.com']);

    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($receptionist, 'receptionist');

    $this->actingAs($receptionist)->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index'))
        ->assertForbidden();

    unset($owner);
});

test('scheduling data is tenant isolated', function (): void {
    [$ownerA, $tenantA] = schedulingWorkspace('Owner A');
    [$ownerB, $tenantB] = schedulingWorkspace('Owner B');

    $this->actingAs($ownerA)->withSession(['tenant_id' => $tenantA->id])
        ->post(route('scheduling.holidays.store'), [
            'holiday_date' => '2026-10-06',
            'reason' => 'Tenant A only',
        ])
        ->assertRedirect();

    $this->actingAs($ownerB)->withSession(['tenant_id' => $tenantB->id])
        ->get(route('scheduling.index'))
        ->assertOk()
        ->assertDontSee('Tenant A only');

    app(CurrentTenant::class)->set($tenantB);

    expect(BusinessHoliday::query()->count())->toBe(0);

    unset($ownerA);
});
