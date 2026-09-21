<?php

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingLock;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerIdentity;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Service\Actions\AssignServiceToStaff;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([ModuleSeeder::class, BusinessTypeSeeder::class]);
});

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function bookingTenant(string $slug, string $timezone = 'Africa/Cairo'): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    \App\Domain\Business\Models\BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => $timezone,
    ]);

    return $tenant;
}

function bookingUser(Tenant $tenant, string $email): User
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

function bookingService(): Service
{
    return app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 10,
    ]);
}

test('availability honors business hours, breaks, staff hours and buffer', function (): void {
    $tenant = bookingTenant('alpha');
    $service = bookingService();

    $user = bookingUser($tenant, 'availability@example.com');
    $staff = StaffProfile::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'display_name' => 'Staff A',
        'status' => StaffStatus::Active,
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '10:00', 'closes_at' => '16:00'],
    ]);

    \App\Domain\Scheduling\Models\BusinessBreak::query()->create([
        'day_of_week' => DayOfWeek::Monday->value,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
        'label' => 'Lunch',
    ]);

    app(AssignServiceToStaff::class)->handle($service, $staff);

    $date = CarbonImmutable::parse('2026-09-28', 'Africa/Cairo');
    $slots = app(AvailabilityService::class)->slots($service, $date, $staff);

    expect($slots)->not->toBeEmpty()
        ->and($slots[0]['start']->format('H:i'))->toBe('10:00')
        ->and(end($slots)['start']->format('H:i'))->toBe('15:15')
        ->and(collect($slots)->contains(fn (array $slot): bool => $slot['start']->format('H:i') === '13:00'))->toBeFalse()
        ->and(collect($slots)->contains(fn (array $slot): bool => $slot['start']->format('H:i') === '13:45'))->toBeFalse()
        ->and(collect($slots)->contains(fn (array $slot): bool => $slot['start']->format('H:i') === '14:00'))->toBeTrue();
});

test('holiday and staff day off remove availability', function (): void {
    $tenant = bookingTenant('holiday');
    $service = bookingService();
    $user = bookingUser($tenant, 'holiday@example.com');
    $staff = StaffProfile::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'display_name' => 'Staff Holiday',
        'status' => StaffStatus::Active,
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);
    app(SetStaffWorkingHours::class)->handle($staff, [
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);
    app(AssignServiceToStaff::class)->handle($service, $staff);

    $date = CarbonImmutable::parse('2026-09-28', 'Africa/Cairo');

    BusinessHoliday::query()->create([
        'holiday_date' => $date->toDateString(),
        'reason' => 'National holiday',
    ]);

    expect(app(AvailabilityService::class)->slots($service, $date, $staff))->toBeEmpty();

    BusinessHoliday::query()->delete();

    StaffDayOff::query()->create([
        'staff_id' => $staff->id,
        'starts_on' => $date->toDateString(),
        'ends_on' => $date->toDateString(),
        'reason' => 'Leave',
    ]);

    expect(app(AvailabilityService::class)->slots($service, $date, $staff))->toBeEmpty();
});

test('customer is deduplicated by normalized phone within a tenant', function (): void {
    $tenant = bookingTenant('customers');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $first = app(CreateBooking::class)->handle(
        $service,
        'Ahmed',
        '+20 (100) 123-4567',
        'ahmed@example.com',
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    $second = app(CreateBooking::class)->handle(
        $service,
        'Ahmed Updated',
        '201001234567',
        'ahmed2@example.com',
        CarbonImmutable::parse('2026-09-28 11:00', 'Africa/Cairo'),
    );

    expect(Customer::query()->count())->toBe(1)
        ->and($first->customer_id)->toBe($second->customer_id)
        ->and($second->customer->name)->toBe('Ahmed Updated')
        ->and(app(CustomerIdentity::class)->normalizePhone('+20 (100) 123-4567'))->toBe('201001234567');
});

test('same resource cannot be double booked', function (): void {
    $tenant = bookingTenant('double-book');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $first = app(CreateBooking::class)->handle(
        $service,
        'First Customer',
        '01000000001',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    expect($first->status)->toBe(BookingStatus::Pending);

    expect(fn () => app(CreateBooking::class)->handle(
        $service,
        'Second Customer',
        '01000000002',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    ))->toThrow(RuntimeException::class);
});

test('assigned staff are allocated independently at the same time', function (): void {
    $tenant = bookingTenant('staff-allocation');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $staffUsers = [
        bookingUser($tenant, 'staff1@example.com'),
        bookingUser($tenant, 'staff2@example.com'),
    ];

    $staff = [];
    foreach ($staffUsers as $index => $user) {
        $staff[] = StaffProfile::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'display_name' => 'Staff '.($index + 1),
            'status' => StaffStatus::Active,
        ]);

        app(SetStaffWorkingHours::class)->handle($staff[$index], [
            ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
        ]);

        app(AssignServiceToStaff::class)->handle($service, $staff[$index]);
    }

    $one = app(CreateBooking::class)->handle(
        $service,
        'Customer One',
        '01000000011',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    $two = app(CreateBooking::class)->handle(
        $service,
        'Customer Two',
        '01000000012',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    expect($one->staff_id)->not->toBeNull()
        ->and($two->staff_id)->not->toBe($one->staff_id);

    expect(fn () => app(CreateBooking::class)->handle(
        $service,
        'Customer Three',
        '01000000013',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    ))->toThrow(RuntimeException::class);
});

test('booking status lifecycle is validated and historized', function (): void {
    $tenant = bookingTenant('lifecycle');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $user = bookingUser($tenant, 'manager@example.com');
    $this->actingAs($user);

    $booking = app(CreateBooking::class)->handle(
        $service,
        'Customer',
        '01000000021',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    app(UpdateBookingStatus::class)->handle($booking, BookingStatus::Confirmed);
    app(UpdateBookingStatus::class)->handle($booking, BookingStatus::Completed);

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed)
        ->and($booking->statusHistory()->count())->toBe(3);

    expect(fn () => app(UpdateBookingStatus::class)->handle($booking->fresh(), BookingStatus::Cancelled))
        ->toThrow(RuntimeException::class);
});

test('booking history database foreign key is tenant safe', function (): void {
    $tenantA = bookingTenant('history-a');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $booking = app(CreateBooking::class)->handle(
        $service,
        'Customer',
        '01000000031',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    );

    $tenantB = bookingTenant('history-b');

    expect(fn () => DB::table('booking_status_history')->insert([
        'tenant_id' => $tenantB->id,
        'booking_id' => $booking->id,
        'from_status' => 'pending',
        'to_status' => 'confirmed',
        'created_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('booking from another tenant is rejected', function (): void {
    $tenantA = bookingTenant('cross-a');
    $service = bookingService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    $tenantB = bookingTenant('cross-b');

    expect(fn () => app(CreateBooking::class)->handle(
        $service,
        'Intruder',
        '01000000041',
        null,
        CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo'),
    ))->toThrow(LogicException::class);
});
