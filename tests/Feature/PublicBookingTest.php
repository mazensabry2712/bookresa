<?php

use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Service\Actions\AssignServiceToStaff;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-27 08:00:00', 'Africa/Cairo'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    app(CurrentTenant::class)->clear();
});

function publicTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'description' => ['en' => 'Public booking business'],
        'timezone' => 'Africa/Cairo',
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    return $tenant;
}

test('public business page is available without authentication', function (): void {
    $tenant = publicTenant('public-clinic');

    app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.show', $tenant->slug))
        ->assertOk()
        ->assertSee('Public booking business')
        ->assertSee('Consultation')
        ->assertSee('data-step-panel="1"', false)
        ->assertSee('data-step-panel="2"', false)
        ->assertSee('data-step-panel="3"', false)
        ->assertSee('data-booking-service-card', false)
        ->assertSee('data-booking-any-staff', false)
        ->assertSee('data-wizard-next', false);
});

test('public booking wizard supports date input events and delegated time-slot selection', function (): void {
    $tenant = publicTenant('public-booking-wizard-events');

    app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.show', $tenant->slug))
        ->assertOk()
        ->assertSee("dateInput.addEventListener('input', scheduleDateAvailability)", false)
        ->assertSee("dateInput.addEventListener('change', scheduleDateAvailability)", false)
        ->assertSee("slotsContainer.addEventListener('click'", false)
        ->assertSee("target.closest('button[data-booking-slot]')", false);
});

test('public availability is tenant scoped and returns slots', function (): void {
    $tenant = publicTenant('availability-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
        'buffer_minutes' => 10,
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]));

    $response->assertOk()
        ->assertJsonStructure(['data']);

    expect($response->json('data'))->not->toBeEmpty();
});

test('public availability groups a time shared by multiple available staff members', function (): void {
    $tenant = publicTenant('public-team-availability');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    foreach ([
        ['email' => 'public-doctor-one@example.com', 'name' => 'Doctor One'],
        ['email' => 'public-doctor-two@example.com', 'name' => 'Doctor Two'],
    ] as $definition) {
        $user = User::factory()->create(['email' => $definition['email']]);
        $staff = StaffProfile::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'display_name' => $definition['name'],
            'status' => StaffStatus::Active,
        ]);
        app(AssignServiceToStaff::class)->handle($service, $staff);
    }

    app(CurrentTenant::class)->clear();

    $response = $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]));

    $response->assertOk();

    $times = collect($response->json('data'))->pluck('time');

    expect($times)->not->toBeEmpty()
        ->and($times->unique()->count())->toBe($times->count())
        ->and($response->json('data.0.available_staff_count'))->toBe(2);
});

test('public booking availability follows each selected doctor weekly schedule', function (): void {
    $tenant = publicTenant('public-doctor-schedules');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $doctors = [];
    foreach ([
        ['email' => 'public-schedule-morning@example.com', 'name' => 'Morning Doctor', 'opens' => '09:00', 'closes' => '11:00'],
        ['email' => 'public-schedule-afternoon@example.com', 'name' => 'Afternoon Doctor', 'opens' => '14:00', 'closes' => '16:00'],
    ] as $definition) {
        $user = User::factory()->create(['email' => $definition['email']]);
        $staff = StaffProfile::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'display_name' => $definition['name'],
            'status' => StaffStatus::Active,
        ]);
        app(AssignServiceToStaff::class)->handle($service, $staff);
        app(SetStaffWorkingHours::class)->handle($staff, [
            ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => $definition['opens'], 'closes_at' => $definition['closes']],
            ['day_of_week' => DayOfWeek::Tuesday->value, 'is_closed' => true],
            ['day_of_week' => DayOfWeek::Wednesday->value, 'is_closed' => true],
            ['day_of_week' => DayOfWeek::Thursday->value, 'is_closed' => true],
            ['day_of_week' => DayOfWeek::Friday->value, 'is_closed' => true],
            ['day_of_week' => DayOfWeek::Saturday->value, 'is_closed' => true],
            ['day_of_week' => DayOfWeek::Sunday->value, 'is_closed' => true],
        ]);
        $doctors[$definition['name']] = $staff;
    }

    app(CurrentTenant::class)->clear();

    $morningResponse = $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'staff_id' => $doctors['Morning Doctor']->id,
        'date' => '2026-09-28',
    ]))->assertOk();

    $afternoonResponse = $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'staff_id' => $doctors['Afternoon Doctor']->id,
        'date' => '2026-09-28',
    ]))->assertOk();

    // The public booking page's automatic-staff view merges only the times
    // that fall within at least one assigned doctor's own schedule.
    $automaticResponse = $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]))->assertOk();

    $morningTimes = collect($morningResponse->json('data'))->pluck('time')->all();
    $afternoonTimes = collect($afternoonResponse->json('data'))->pluck('time')->all();
    $automaticSlots = collect($automaticResponse->json('data'));
    $automaticTimes = $automaticSlots->pluck('time')->all();

    expect($morningTimes)->toBe(['09:00', '09:15', '09:30', '09:45', '10:00', '10:15', '10:30'])
        ->and($afternoonTimes)->toBe(['14:00', '14:15', '14:30', '14:45', '15:00', '15:15', '15:30'])
        ->and($automaticTimes)->toBe([
            '09:00', '09:15', '09:30', '09:45', '10:00', '10:15', '10:30',
            '14:00', '14:15', '14:30', '14:45', '15:00', '15:15', '15:30',
        ])
        ->and($automaticSlots->first()['available_staff_count'])->toBe(1)
        ->and($automaticSlots->last()['available_staff_count'])->toBe(1);
});

test('public availability explains when all assigned staff are inactive', function (): void {
    $tenant = publicTenant('public-inactive-staff');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $user = User::factory()->create(['email' => 'public-inactive-doctor@example.com']);
    $staff = StaffProfile::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'display_name' => 'Inactive Doctor',
        'status' => StaffStatus::Active,
    ]);
    app(AssignServiceToStaff::class)->handle($service, $staff);
    $staff->update(['status' => StaffStatus::Inactive]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]))
        ->assertOk()
        ->assertExactJson([
            'data' => [],
            'reason' => 'no_active_staff',
        ]);
});

test('inactive services cannot be booked through public availability or direct submission', function (): void {
    $tenant = publicTenant('inactive-public-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Inactive consultation', 'ar' => 'استشارة غير نشطة'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => false,
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]))
        ->assertOk()
        ->assertExactJson(['data' => []]);

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ])->assertSessionHasErrors('booking');

    expect(Booking::withoutGlobalScopes()->count())->toBe(0);
});

test('public customer can book without an account', function (): void {
    $tenant = publicTenant('book-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ]);

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);
    $booking->load('customer');

    $response->assertRedirect(URL::signedRoute('public.booking.canonical.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]));

    expect($booking->customer->name)->toBe('Ahmed')
        ->and($booking->service_id)->toBe($service->id)
        ->and(preg_match('/^BR-\\d{6}$/', $booking->booking_reference))->toBe(1);
});

test('public booking confirmation requires a signed url', function (): void {
    $tenant = publicTenant('confirmation-secure');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ])->assertRedirect();

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    $this->get(route('public.booking.canonical.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]))->assertForbidden();

    $this->get(URL::signedRoute('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]))
        ->assertOk()
        ->assertSee($booking->booking_reference);
});

test('public booking cannot use a service from another tenant', function (): void {
    $tenantA = publicTenant('tenant-a');
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Private Service'],
        'price_minor' => 10000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();
    $tenantB = publicTenant('tenant-b');
    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.canonical.store', $tenantB->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Intruder',
    ])->assertSessionHasErrors('service_id');

    expect(Booking::withoutGlobalScopes()->count())->toBe(0);
});

test('public booking page renders localized Arabic content', function (): void {
    $tenant = publicTenant('arabic-clinic');

    app(CurrentTenant::class)->set($tenant);
    $tenant->profile()->update([
        'name' => ['en' => 'Arabic Clinic', 'ar' => 'عيادة عربية'],
        'description' => ['en' => 'English description', 'ar' => 'وصف عربي'],
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.show', $tenant->slug).'?locale=ar')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('عيادة عربية')
        ->assertSee('وصف عربي')
        ->assertSee('الخدمة')
        ->assertSee('اختر خدمة');

    expect(app()->getLocale())->toBe('ar');
});

test('booking confirmation renders localized Arabic content', function (): void {
    $tenant = publicTenant('arabic-confirmation');

    app(CurrentTenant::class)->set($tenant);
    $tenant->profile()->update([
        'name' => ['en' => 'Arabic Clinic', 'ar' => 'عيادة عربية'],
        'description' => ['en' => 'English description', 'ar' => 'وصف عربي'],
    ]);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ])->assertRedirect();

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    $this->get(URL::signedRoute('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
        'locale' => 'ar',
    ]))
        ->assertOk()
        ->assertSee('تم استلام الحجز')
        ->assertSee('استشارة')
        ->assertSee('سبتمبر')
        ->assertSee('نسخ رقم الحجز')
        ->assertSee('احجز موعدًا آخر');
});
