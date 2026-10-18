<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\BookingNotification;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $now = CarbonImmutable::parse('2026-10-18 12:00:00', 'UTC');
    CarbonImmutable::setTestNow($now);
    Carbon::setTestNow($now);
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
    app(CurrentTenant::class)->clear();
});

function reminderTestBooking(string $slug): Booking
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Reminder Test', 'ar' => 'اختبار التذكير'],
        'timezone' => 'UTC',
    ]);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Reminder Customer',
        'phone' => '01000000999',
        'normalized_phone' => '201000000999',
        'email' => 'reminder@example.com',
    ]);

    return Booking::query()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => CarbonImmutable::now('UTC')->addHours(24),
        'ends_at' => CarbonImmutable::now('UTC')->addHours(24)->addMinutes(30),
        'block_ends_at' => CarbonImmutable::now('UTC')->addHours(24)->addMinutes(30),
        'status' => BookingStatus::Confirmed,
        'payment_status' => 'unpaid',
        'booking_reference' => 'BR-REMINDER-'.strtoupper(substr(sha1($slug), 0, 8)),
    ]);
}

test('booking reminder is marked sent only after notification dispatch and does not duplicate', function (): void {
    Notification::fake();
    $booking = reminderTestBooking('reminder-command-success');
    $customer = $booking->customer;

    app(CurrentTenant::class)->clear();

    $this->artisan('bookresa:send-booking-reminders')
        ->assertExitCode(0);

    expect(Booking::withoutGlobalScopes()->findOrFail($booking->getKey())->reminder_sent_at)->not->toBeNull();

    Notification::assertSentTo($customer, BookingNotification::class);

    $this->artisan('bookresa:send-booking-reminders')
        ->assertExitCode(0);

    Notification::assertSentToTimes($customer, BookingNotification::class, 1);
});

test('failed booking reminder dispatch releases the claim for a later retry', function (): void {
    $booking = reminderTestBooking('reminder-command-failure');
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Queue is temporarily unavailable.'));
    $this->app->instance(Dispatcher::class, $dispatcher);

    app(CurrentTenant::class)->clear();

    $this->artisan('bookresa:send-booking-reminders')
        ->assertExitCode(0);

    expect(Booking::withoutGlobalScopes()->findOrFail($booking->getKey())->reminder_sent_at)->toBeNull();
});
