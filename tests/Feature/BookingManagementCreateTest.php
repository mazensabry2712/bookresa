<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
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
        ->assertSee(__('app.booking_ui.create_booking'));
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
        ->get(route('booking.management.availability', [
            'service_id' => $service->id,
            'date' => $date->toDateString(),
        ]))
        ->assertOk()
        ->assertJsonPath('data.0.time', '09:00');
});
