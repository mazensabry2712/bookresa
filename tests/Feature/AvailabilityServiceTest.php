<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Actions\AddBusinessBreak;
use App\Domain\Scheduling\Actions\AddStaffAvailability;
use App\Domain\Scheduling\Actions\AddStaffDayOff;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Actions\UpsertBusinessHoliday;
use App\Domain\Scheduling\Actions\UpsertSpecialWorkingHour;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Service\Actions\AssignServiceToStaff;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
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

function availabilityTenant(string $name): Tenant
{
    $owner = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    return app(CreateBusiness::class)->handle($owner, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);
}

function availabilityService(Tenant $tenant, string $name = 'Consultation'): Service
{
    app(CurrentTenant::class)->set($tenant);

    return app(CreateService::class)->handle([
        'name' => ['en' => $name, 'ar' => $name],
        'price_minor' => 10000,
        'duration_minutes' => 60,
        'buffer_minutes' => 0,
        'currency' => 'EGP',
    ]);
}

function availabilityStaff(Tenant $tenant, Service $service): StaffProfile
{
    app(CurrentTenant::class)->set($tenant);

    $staffUser = User::factory()->create();
    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff');

    app(AssignServiceToStaff::class)->handle($service, $staff);

    return $staff;
}

function mondayInCairo(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-05 00:00:00', 'Africa/Cairo');
}

test('business break is removed from generated service slots', function (): void {
    $tenant = availabilityTenant('Availability Break');
    $service = availabilityService($tenant);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(AddBusinessBreak::class)->handle([
        'day_of_week' => DayOfWeek::Monday->value,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
    ]);

    $slots = app(AvailabilityService::class)->slots($service, mondayInCairo());

    expect($slots)->not->toBeEmpty()
        ->and(collect($slots)->pluck('start')->map(fn (CarbonImmutable $value): string => $value->format('H:i'))->all())
        ->not->toContain('13:00')
        ->and(collect($slots)->pluck('start')->map(fn (CarbonImmutable $value): string => $value->format('H:i'))->all())
        ->toContain('12:00', '14:00');
});

test('business holiday produces no slots', function (): void {
    $tenant = availabilityTenant('Availability Holiday');
    $service = availabilityService($tenant);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(UpsertBusinessHoliday::class)->handle([
        'holiday_date' => '2026-10-05',
        'reason' => 'Closed',
    ]);

    expect(app(AvailabilityService::class)->slots($service, mondayInCairo()))->toBe([]);
});

test('special working hours override regular business hours', function (): void {
    $tenant = availabilityTenant('Availability Special');
    $service = availabilityService($tenant);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(UpsertSpecialWorkingHour::class)->handle([
        'work_date' => '2026-10-05',
        'opens_at' => '11:00',
        'closes_at' => '13:00',
        'reason' => 'Short day',
    ]);

    $slots = app(AvailabilityService::class)->slots($service, mondayInCairo());

    expect(collect($slots)->pluck('start')->map(fn (CarbonImmutable $value): string => $value->format('H:i'))->all())
        ->toBe(['11:00', '11:15']);
});

test('closed staff working hours prevent assigned staff slots', function (): void {
    $tenant = availabilityTenant('Availability Staff Closed');
    $service = availabilityService($tenant);
    $staff = availabilityStaff($tenant, $service);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'is_closed' => true],
    ]);

    expect(app(AvailabilityService::class)->slots($service, mondayInCairo(), $staff))->toBe([]);
});

test('explicit staff availability takes precedence over recurring staff hours', function (): void {
    $tenant = availabilityTenant('Availability Explicit');
    $service = availabilityService($tenant);
    $staff = availabilityStaff($tenant, $service);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(AddStaffAvailability::class)->handle($staff, [
        'available_date' => '2026-10-05',
        'starts_at' => '14:00',
        'ends_at' => '16:00',
    ]);

    $slots = app(AvailabilityService::class)->slots($service, mondayInCairo(), $staff);

    expect(collect($slots)->pluck('start')->map(fn (CarbonImmutable $value): string => $value->format('H:i'))->all())
        ->toBe(['14:00', '14:15', '14:30', '14:45', '15:00']);
});

test('staff day off produces no slots even when staff is otherwise scheduled', function (): void {
    $tenant = availabilityTenant('Availability Day Off');
    $service = availabilityService($tenant);
    $staff = availabilityStaff($tenant, $service);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(AddStaffDayOff::class)->handle($staff, [
        'starts_on' => '2026-10-05',
        'ends_on' => '2026-10-05',
        'reason' => 'Leave',
    ]);

    expect(app(AvailabilityService::class)->slots($service, mondayInCairo(), $staff))->toBe([]);
});
