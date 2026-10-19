<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

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

test('booking management create screen is available to users with booking creation permission', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Internal Booking Clinic'],
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.create', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee(__('app.booking_ui.new_booking'))
        ->assertSee(__('app.booking_ui.customer_name'))
        ->assertSee(__('app.booking_ui.create_booking'))
        ->assertSee(__('app.booking_ui.no_active_services'))
        ->assertSee(__('app.booking_ui.manage_services'))
        ->assertSee('data-booking-retry', false)
        ->assertSee('data-booking-selected-time', false)
        ->assertSee('chooseTime:', false)
        ->assertSee('data-booking-auto-staff', false);
});

test('booking management can create a pending booking using the workspace availability rules', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Create Booking Clinic'],
    );

    $tomorrow = now($tenant->profile->timezone ?? 'UTC')->addDay()->startOfDay()->addHours(10);

    $service = app(CurrentTenant::class)->run($tenant, function () use ($tenant): Service {
        Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
            'description' => ['en' => null, 'ar' => null],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        BusinessWorkingHour::query()->create([
            'tenant_id' => $tenant->id,
            'day_of_week' => DayOfWeek::from(now($tenant->profile->timezone ?? 'UTC')->addDay()->dayOfWeekIso),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);

        return Service::query()->where('tenant_id', $tenant->id)->firstOrFail();
    });

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('booking.management.store', ['tenant' => $tenant->slug]), [
            'service_id' => $service->id,
            'date' => $tomorrow->toDateString(),
            'time' => $tomorrow->format('H:i'),
            'name' => 'Internal Customer',
            'phone' => '+201000000001',
            'email' => 'internal-customer@example.com',
            'notes' => 'Created by staff.',
        ])
        ->assertRedirect();

    $booking = app(CurrentTenant::class)->run($tenant, function () use ($tenant): Booking {
        return Booking::query()
            ->with('customer')
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->firstOrFail();
    });

    expect($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->customer->name)->toBe('Internal Customer')
        ->and($booking->service_id)->toBe($service->id);
});


test('booking management links the selected existing customer by ID without creating a duplicate', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();
    $tenant = app(CreateBusiness::class)->handle($user, $type, ['name' => 'Existing Customer Booking Clinic']);
    $timezone = $tenant->profile->timezone ?: 'UTC';
    $start = now($timezone)->addDay()->startOfDay()->addHours(10);

    [$service, $customer] = app(CurrentTenant::class)->run($tenant, function () use ($tenant, $start): array {
        $service = Service::query()->create([
            'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
            'description' => ['en' => null, 'ar' => null],
            'price_minor' => 15000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        BusinessWorkingHour::query()->create([
            'tenant_id' => $tenant->id,
            'day_of_week' => DayOfWeek::from($start->dayOfWeekIso),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);

        $customer = Customer::query()->create([
            'name' => 'Existing Client With No Contact Details',
            'normalized_phone' => null,
            'phone' => null,
            'email' => null,
        ]);

        return [$service, $customer];
    });

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('booking.management.store', ['tenant' => $tenant->slug]), [
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'date' => $start->toDateString(),
            'time' => $start->format('H:i'),
        ])
        ->assertRedirect();

    app(CurrentTenant::class)->run($tenant, function () use ($customer, $service): void {
        $booking = Booking::query()->with('customer')->latest('id')->firstOrFail();

        expect($booking->customer_id)->toBe($customer->id)
            ->and($booking->customer->name)->toBe('Existing Client With No Contact Details')
            ->and(Customer::query()->count())->toBe(1)
            ->and($booking->service_name_snapshot)->toBe(['en' => 'Consultation', 'ar' => 'كشف'])
            ->and($booking->service_price_minor_snapshot)->toBe(15000)
            ->and($booking->service_currency_snapshot)->toBe('EGP')
            ->and($booking->service_duration_minutes_snapshot)->toBe(30)
            ->and($booking->service_id)->toBe($service->id);
    });
});

test('internal availability endpoint returns available slots for the selected service and date', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Internal Availability Clinic'],
    );

    $date = now($tenant->profile->timezone ?? 'UTC')->addDay()->startOfDay();

    $service = app(CurrentTenant::class)->run($tenant, function () use ($tenant, $date): Service {
        Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
            'description' => ['en' => null, 'ar' => null],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        BusinessWorkingHour::query()->create([
            'tenant_id' => $tenant->id,
            'day_of_week' => DayOfWeek::from($date->dayOfWeekIso),
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);

        return Service::query()->firstOrFail();
    });

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.availability', ['tenant' => $tenant->slug,
            'service_id' => $service->id,
            'date' => $date->toDateString(),
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.time', '09:00');
});
