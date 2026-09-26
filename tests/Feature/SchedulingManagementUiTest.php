<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Actions\AddStaffMember;
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

function schedulingUiWorkspace(string $name = 'Scheduling Workspace'): array
{
    $user = User::factory()->create(['name' => $name]);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$user, $tenant];
}

test('owner can view and manage business scheduling', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index'))
        ->assertOk()
        ->assertSee('Scheduling')
        ->assertSee('Business working hours');

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update'), ['hours' => $hours])
        ->assertRedirect(route('scheduling.index'));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.breaks.store'), [
            'day_of_week' => DayOfWeek::Monday->value,
            'starts_at' => '13:00',
            'ends_at' => '14:00',
            'label' => 'Lunch',
        ])
        ->assertRedirect(route('scheduling.index'));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.holidays.store'), [
            'holiday_date' => '2026-11-01',
            'reason' => 'Public holiday',
        ])
        ->assertRedirect(route('scheduling.index'));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.special-hours.store'), [
            'work_date' => '2026-11-02',
            'opens_at' => '10:00',
            'closes_at' => '15:00',
            'reason' => 'Short day',
        ])
        ->assertRedirect(route('scheduling.index'));

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(7)
        ->and(BusinessBreak::query()->count())->toBe(1)
        ->and(BusinessHoliday::query()->count())->toBe(1)
        ->and(SpecialWorkingHour::query()->count())->toBe(1)
        ->and(BusinessWorkingHour::query()->where('day_of_week', DayOfWeek::Monday->value)->first()->opens_at)->toBe('09:00');
});

test('owner can manage a selected staff schedule', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace('Staff Scheduling Workspace');
    app(CurrentTenant::class)->set($tenant);

    $staffUser = User::factory()->create(['email' => 'schedule-ui-staff@example.com']);
    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff');

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '10:00',
        'closes_at' => '18:00',
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index', ['staff' => $staff->id]))
        ->assertOk()
        ->assertSee($staff->display_name);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.staff-hours.update', $staff), ['hours' => $hours])
        ->assertRedirect(route('scheduling.index', ['staff' => $staff->id]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-days-off.store', $staff), [
            'starts_on' => '2026-11-10',
            'ends_on' => '2026-11-12',
            'reason' => 'Leave',
        ])
        ->assertRedirect(route('scheduling.index', ['staff' => $staff->id]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-availability.store', $staff), [
            'available_date' => '2026-11-14',
            'starts_at' => '11:00',
            'ends_at' => '14:00',
        ])
        ->assertRedirect(route('scheduling.index', ['staff' => $staff->id]));

    app(CurrentTenant::class)->set($tenant);

    expect(StaffWorkingHour::query()->count())->toBe(7)
        ->and(StaffDayOff::query()->count())->toBe(1)
        ->and(StaffAvailability::query()->count())->toBe(1);
});

test('receptionist can view scheduling but cannot mutate it', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace('Reception Scheduling Workspace');
    app(CurrentTenant::class)->set($tenant);

    $receptionist = User::factory()->create([
        'email' => 'schedule-receptionist@example.com',
    ]);

    app(AddStaffMember::class)->handle($receptionist, 'receptionist');

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
    ])->all();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index'))
        ->assertOk();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update'), ['hours' => $hours])
        ->assertForbidden();

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(0);

    unset($owner);
});

test('scheduling management is tenant isolated', function (): void {
    [$ownerA, $tenantA] = schedulingUiWorkspace('Scheduling Tenant A');
    [$ownerB, $tenantB] = schedulingUiWorkspace('Scheduling Tenant B');

    app(CurrentTenant::class)->set($tenantA);

    $breakA = BusinessBreak::query()->create([
        'tenant_id' => $tenantA->id,
        'day_of_week' => DayOfWeek::Monday->value,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
        'label' => 'Tenant A break',
    ]);

    $staffAUser = User::factory()->create(['email' => 'schedule-tenant-a@example.com']);
    $staffA = app(AddStaffMember::class)->handle($staffAUser, 'staff');

    app(CurrentTenant::class)->set($tenantB);

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->get(route('scheduling.index'))
        ->assertOk()
        ->assertDontSee('Tenant A break')
        ->assertDontSee($staffA->display_name);

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->delete(route('scheduling.breaks.destroy', $breakA))
        ->assertNotFound();

    app(CurrentTenant::class)->set($tenantA);

    expect(BusinessBreak::query()->where('label', 'Tenant A break')->exists())->toBeTrue();

    unset($ownerA, $ownerB, $staffA);
});

test('staff schedule deletes cannot target another staff member', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace('Staff Schedule Isolation Workspace');
    app(CurrentTenant::class)->set($tenant);

    $staffAUser = User::factory()->create(['email' => 'schedule-delete-a@example.com']);
    $staffBUser = User::factory()->create(['email' => 'schedule-delete-b@example.com']);

    $staffA = app(AddStaffMember::class)->handle($staffAUser, 'staff');
    $staffB = app(AddStaffMember::class)->handle($staffBUser, 'staff');

    $dayOff = StaffDayOff::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staffB->id,
        'starts_on' => '2026-11-10',
        'ends_on' => '2026-11-10',
    ]);

    $availability = StaffAvailability::query()->create([
        'tenant_id' => $tenant->id,
        'staff_id' => $staffB->id,
        'available_date' => '2026-11-11',
        'starts_at' => '10:00',
        'ends_at' => '12:00',
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.staff-days-off.destroy', [$staffA, $dayOff]))
        ->assertNotFound();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.staff-availability.destroy', [$staffA, $availability]))
        ->assertNotFound();

    app(CurrentTenant::class)->set($tenant);

    expect(StaffDayOff::query()->whereKey($dayOff->id)->exists())->toBeTrue()
        ->and(StaffAvailability::query()->whereKey($availability->id)->exists())->toBeTrue();

    unset($staffA, $staffB);
});

test('scheduling requests reject invalid time windows', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace('Scheduling Validation Workspace');
    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.breaks.store'), [
            'day_of_week' => DayOfWeek::Monday->value,
            'starts_at' => '15:00',
            'ends_at' => '14:00',
        ])
        ->assertSessionHasErrors('ends_at');

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessBreak::query()->count())->toBe(0);
});
