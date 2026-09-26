<?php

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

test('booking local time is persisted as the same UTC instant', function (): void {
    $tenant = Tenant::query()->create([
        'slug' => 'timezone-policy',
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Timezone Policy', 'ar' => 'سياسة الوقت'],
        'timezone' => 'Africa/Cairo',
    ]);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 10000,
        'duration_minutes' => 60,
        'buffer_minutes' => 0,
        'currency' => 'EGP',
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        [
            'day_of_week' => DayOfWeek::Monday->value,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
        ],
    ]);

    $localStart = CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo');

    $booking = app(CreateBooking::class)->handle(
        $service,
        'Timezone Customer',
        '01000000123',
        'timezone@example.com',
        $localStart,
    );

    expect($booking)->toBeInstanceOf(Booking::class)
        ->and($booking->starts_at->equalTo($localStart->utc()))->toBeTrue()
        ->and($booking->ends_at->equalTo($localStart->addHour()->utc()))->toBeTrue()
        ->and($booking->starts_at->getTimezone()->getName())->toBe('UTC');
});
