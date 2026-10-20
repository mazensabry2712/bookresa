<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
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

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.step', 'ready');
    data_set($settings, 'onboarding.completed', true);
    $tenant->forceFill(['settings' => $settings])->save();

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Test Core Plan'],
        'description' => ['en' => 'Test core plan'],
        'price_minor' => 0,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 100,
        'additional_customer_price_minor' => 0,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => CarbonImmutable::now()->addMonth(),
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    return [$user, $tenant];
}

test('owner can view and manage business scheduling', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Scheduling')
        ->assertSee('Business working hours')
        ->assertSee('Quickly set working hours')
        ->assertSee('Apply to open days only')
        ->assertSee('Apply to all 7 days')
        ->assertSee('id="business-hours"', false)
        ->assertSee('role="tablist"', false)
        ->assertSee('data-schedule-tab="business-hours"', false)
        ->assertSee('data-schedule-tab="business-exceptions"', false)
        ->assertSee('data-schedule-tab="staff-schedule"', false)
        ->assertSee('<details id="business-exceptions" data-schedule-panel="business-exceptions"', false)
        ->assertSee('<details id="staff-schedule" data-schedule-panel="staff-schedule"', false)
        ->assertSee('data-business-hours-form', false)
        ->assertSee('businessHoursRows.filter(', false)
        ->assertSee('const activateSchedulePanel = (panelId) => {', false);

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), ['hours' => $hours])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.breaks.store', ['tenant' => $tenant->slug]), [
            'day_of_week' => DayOfWeek::Monday->value,
            'starts_at' => '13:00',
            'ends_at' => '14:00',
            'label' => 'Lunch',
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.holidays.store', ['tenant' => $tenant->slug]), [
            'holiday_date' => '2026-11-01',
            'reason' => 'Public holiday',
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.special-hours.store', ['tenant' => $tenant->slug]), [
            'work_date' => '2026-11-02',
            'opens_at' => '10:00',
            'closes_at' => '15:00',
            'reason' => 'Short day',
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(7)
        ->and(BusinessBreak::query()->count())->toBe(1)
        ->and(BusinessHoliday::query()->count())->toBe(1)
        ->and(SpecialWorkingHour::query()->count())->toBe(1)
        ->and(BusinessWorkingHour::query()->where('day_of_week', DayOfWeek::Monday->value)->first()->opens_at)->toBe('09:00');
});

test('each staff member has a separate weekly schedule that can be edited independently', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    $firstStaff = app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'individual-schedule-one@example.com']),
        'staff',
        ['display_name' => 'Dr. Individual One'],
    );
    $secondStaff = app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'individual-schedule-two@example.com']),
        'staff',
        ['display_name' => 'Dr. Individual Two'],
    );

    $firstHours = collect(range(1, 7))->map(fn (int $day): array => $day <= 5
        ? ['day_of_week' => $day, 'opens_at' => '09:00', 'closes_at' => '17:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();
    $secondHours = collect(range(1, 7))->map(fn (int $day): array => $day <= 5
        ? ['day_of_week' => $day, 'opens_at' => '11:00', 'closes_at' => '19:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    app(\App\Domain\Scheduling\Actions\SetStaffWorkingHours::class)->handle($firstStaff, $firstHours);
    app(\App\Domain\Scheduling\Actions\SetStaffWorkingHours::class)->handle($secondStaff, $secondHours);

    $page = $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Dr. Individual One')
        ->assertSee('Dr. Individual Two')
        ->assertSee('data-doctor-schedule', false)
        ->assertSee('name="staff_schedule_id" value="'.$firstStaff->id.'"', false)
        ->assertSee('name="staff_schedule_id" value="'.$secondStaff->id.'"', false);

    expect(substr_count($page->getContent(), 'data-doctor-schedule'))->toBe(2);

    $updatedHours = collect(range(1, 7))->map(fn (int $day): array => $day <= 5
        ? ['day_of_week' => $day, 'opens_at' => '08:00', 'closes_at' => '16:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.staff-hours.update', ['tenant' => $tenant->slug, 'staff' => $firstStaff]), [
            'schedule_form' => 'staff',
            'staff_schedule_id' => $firstStaff->id,
            'hours' => $updatedHours,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug, 'staff' => $firstStaff->getKey()]));

    app(CurrentTenant::class)->set($tenant);
    $firstMonday = StaffWorkingHour::query()
        ->where('staff_id', $firstStaff->id)
        ->where('day_of_week', DayOfWeek::Monday->value)
        ->firstOrFail();
    $secondMonday = StaffWorkingHour::query()
        ->where('staff_id', $secondStaff->id)
        ->where('day_of_week', DayOfWeek::Monday->value)
        ->firstOrFail();

    expect(substr((string) $firstMonday->opens_at, 0, 5))->toBe('08:00')
        ->and(substr((string) $firstMonday->closes_at, 0, 5))->toBe('16:00')
        ->and(substr((string) $secondMonday->opens_at, 0, 5))->toBe('11:00')
        ->and(substr((string) $secondMonday->closes_at, 0, 5))->toBe('19:00');
});

test('doctor schedule table shows effective business hours until a personal schedule is saved', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'business-hours-fallback@example.com']),
        'staff',
        ['display_name' => 'Dr. Schedule Template'],
    );

    $response = $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Dr. Schedule Template')
        ->assertSee('This doctor currently follows the business hours shown here.');

    $html = $response->getContent();
    $cardStart = strpos($html, 'data-doctor-schedule');
    $cardEnd = $cardStart === false ? false : strpos($html, '</article>', $cardStart);
    expect($cardStart)->not->toBeFalse()
        ->and($cardEnd)->not->toBeFalse();

    $card = substr($html, $cardStart, $cardEnd - $cardStart);

    expect($card)->toContain('name="hours[0][opens_at]" value="09:00"')
        ->and($card)->toContain('name="hours[0][closes_at]" value="17:00"');
});

test('owner can apply one weekly schedule to multiple staff without changing individual exceptions', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    $firstStaff = app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'bulk-schedule-one@example.com']),
        'staff',
        ['display_name' => 'Dr. First'],
    );
    $secondStaff = app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'bulk-schedule-two@example.com']),
        'staff',
        ['display_name' => 'Dr. Second'],
    );
    $untouchedStaff = app(AddStaffMember::class)->handle(
        User::factory()->create(['email' => 'bulk-schedule-three@example.com']),
        'staff',
        ['display_name' => 'Dr. Untouched'],
    );

    app(\App\Domain\Scheduling\Actions\AddStaffDayOff::class)->handle($firstStaff, [
        'starts_on' => '2026-11-01',
        'ends_on' => '2026-11-02',
        'reason' => 'Personal leave',
    ]);
    app(\App\Domain\Scheduling\Actions\AddStaffAvailability::class)->handle($secondStaff, [
        'available_date' => '2026-11-03',
        'starts_at' => '10:00',
        'ends_at' => '12:00',
    ]);

    $hours = collect(range(1, 7))->map(fn (int $day): array => $day <= 5
        ? ['day_of_week' => $day, 'opens_at' => '10:00', 'closes_at' => '18:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-hours.bulk', ['tenant' => $tenant->slug]), [
            'staff_ids' => [$firstStaff->id, $secondStaff->id],
            'hours' => $hours,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertSessionHas('status', __('app.scheduling_ui.bulk_staff_hours_updated', ['count' => 2]));

    app(CurrentTenant::class)->set($tenant);

    foreach ([$firstStaff, $secondStaff] as $staffMember) {
        $savedHours = StaffWorkingHour::query()->where('staff_id', $staffMember->id)->orderBy('day_of_week')->get();
        $openHours = $savedHours->where('is_closed', false);
        expect($savedHours)->toHaveCount(7)
            ->and($openHours->every(fn ($hour): bool => substr((string) $hour->opens_at, 0, 5) === '10:00'))->toBeTrue()
            ->and($openHours->every(fn ($hour): bool => substr((string) $hour->closes_at, 0, 5) === '18:00'))->toBeTrue()
            ->and($savedHours->where('is_closed', true)->map(fn ($hour): int => $hour->day_of_week->value)->values()->all())->toBe([6, 7]);
    }

    expect(StaffWorkingHour::query()->where('staff_id', $untouchedStaff->id)->count())->toBe(0)
        ->and(StaffDayOff::query()->where('staff_id', $firstStaff->id)->count())->toBe(1)
        ->and(StaffAvailability::query()->where('staff_id', $secondStaff->id)->count())->toBe(1);
});

test('onboarding requires at least one open business day before continuing to staff setup', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);
    app(CreateService::class)->handle([
        'name' => ['en' => 'Onboarding consultation', 'ar' => 'استشارة الإعداد'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', false);
    data_set($settings, 'onboarding.step', 'hours');
    $tenant->forceFill(['settings' => $settings])->save();

    $closedHours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'is_closed' => 1,
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), [
            'hours' => $closedHours,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors('schedule');

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(0)
        ->and((bool) data_get($tenant->fresh()->settings, 'onboarding.completed'))->toBeFalse();

    $configuredHours = collect(range(1, 7))->map(fn (int $day): array => $day === 1
        ? ['day_of_week' => $day, 'opens_at' => '09:00', 'closes_at' => '17:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), [
            'hours' => $configuredHours,
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(7)
        ->and(BusinessWorkingHour::query()->where('is_closed', false)->count())->toBe(1)
        ->and(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('staff')
        ->and((bool) data_get($tenant->fresh()->settings, 'onboarding.completed'))->toBeFalse();
});

test('saving business hours before creating a service returns onboarding to services', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', false);
    data_set($settings, 'onboarding.step', 'hours');
    $tenant->forceFill(['settings' => $settings])->save();

    $configuredHours = collect(range(1, 7))->map(fn (int $day): array => $day === 1
        ? ['day_of_week' => $day, 'opens_at' => '09:00', 'closes_at' => '17:00']
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), [
            'hours' => $configuredHours,
        ])
        ->assertRedirect(route('services.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->where('is_closed', false)->count())->toBe(1)
        ->and(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('services')
        ->and((bool) data_get($tenant->fresh()->settings, 'onboarding.completed'))->toBeFalse();
});

test('recurring schedule requires valid times on open days and permits closed days without times', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();

    $invalidHours = collect(range(1, 7))->map(fn (int $day): array => $day === 1
        ? ['day_of_week' => $day]
        : ['day_of_week' => $day, 'is_closed' => 1])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), [
            'hours' => $invalidHours,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors(['hours.0.opens_at', 'hours.0.closes_at']);

    $closedHours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'is_closed' => 1,
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), [
            'hours' => $closedHours,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessWorkingHour::query()->count())->toBe(7)
        ->and(BusinessWorkingHour::query()->where('is_closed', true)->count())->toBe(7);
});

test('scheduling one-off and staff availability dates cannot be saved in the past', function (): void {
    [$owner, $tenant] = schedulingUiWorkspace();
    app(CurrentTenant::class)->set($tenant);

    $staffUser = User::factory()->create(['email' => 'schedule-past-date@example.com']);
    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.holidays.store', ['tenant' => $tenant->slug]), [
            'holiday_date' => '2020-01-01',
            'reason' => 'Past holiday',
        ])
        ->assertSessionHasErrors('holiday_date');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.special-hours.store', ['tenant' => $tenant->slug]), [
            'work_date' => '2020-01-02',
            'opens_at' => '10:00',
            'closes_at' => '12:00',
        ])
        ->assertSessionHasErrors('work_date');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-availability.store', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'available_date' => '2020-01-03',
            'starts_at' => '10:00',
            'ends_at' => '12:00',
        ])
        ->assertSessionHasErrors('available_date');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-days-off.store', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'starts_on' => '2020-01-04',
            'ends_on' => '2020-01-05',
            'reason' => 'Past time off',
        ])
        ->assertSessionHasErrors('starts_on');

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessHoliday::query()->count())->toBe(0)
        ->and(SpecialWorkingHour::query()->count())->toBe(0)
        ->and(StaffAvailability::query()->count())->toBe(0)
        ->and(StaffDayOff::query()->count())->toBe(0);
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
        ->get(route('scheduling.index', ['tenant' => $tenant->slug, 'staff' => $staff->id]))
        ->assertOk()
        ->assertSee($staff->display_name);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.staff-hours.update', ['tenant' => $tenant->slug, 'staff' => $staff]), ['hours' => $hours])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug, 'staff' => $staff->id]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-days-off.store', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'starts_on' => '2026-11-10',
            'ends_on' => '2026-11-12',
            'reason' => 'Leave',
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug, 'staff' => $staff->id]));

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('scheduling.staff-availability.store', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'available_date' => '2026-11-14',
            'starts_at' => '11:00',
            'ends_at' => '14:00',
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug, 'staff' => $staff->id]));

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
        ->get(route('scheduling.index', ['tenant' => $tenant->slug]))
        ->assertOk();

    $this->actingAs($receptionist)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update', ['tenant' => $tenant->slug]), ['hours' => $hours])
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
        ->get(route('scheduling.index', ['tenant' => $tenantB->slug]))
        ->assertOk()
        ->assertDontSee('Tenant A break')
        ->assertDontSee($staffA->display_name);

    $this->actingAs($ownerB)
        ->withSession(['tenant_id' => $tenantB->id])
        ->delete(route('scheduling.breaks.destroy', ['tenant' => $tenantB->slug, 'break' => $breakA]))
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
        ->delete(route('scheduling.staff-days-off.destroy', ['tenant' => $tenant->slug, 'staff' => $staffA, 'dayOff' => $dayOff]))
        ->assertNotFound();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->delete(route('scheduling.staff-availability.destroy', ['tenant' => $tenant->slug, 'staff' => $staffA, 'availability' => $availability]))
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
        ->post(route('scheduling.breaks.store', ['tenant' => $tenant->slug]), [
            'day_of_week' => DayOfWeek::Monday->value,
            'starts_at' => '15:00',
            'ends_at' => '14:00',
        ])
        ->assertSessionHasErrors('ends_at');

    app(CurrentTenant::class)->set($tenant);

    expect(BusinessBreak::query()->count())->toBe(0);
});
