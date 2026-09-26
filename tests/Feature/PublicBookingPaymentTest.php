<?php

use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function paidBookingTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'description' => ['en' => 'Payment booking business'],
        'timezone' => 'Africa/Cairo',
        'booking_settings' => [
            'customer_account_required' => false,
            'payment_required' => true,
        ],
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    return $tenant;
}

test('public paid booking redirects to Kashier checkout and stores payment session', function (): void {
    $tenant = paidBookingTenant('paid-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    config([
        'bookresa.payments.kashier' => [
            'mode' => 'test',
            'base_url' => 'https://test-api.kashier.io',
            'merchant_id' => 'MID-123',
            'api_key' => 'api-key',
            'secret_key' => 'secret-key',
            'merchant_redirect' => null,
            'server_webhook' => 'https://example.test/webhooks/kashier',
            'max_failure_attempts' => 3,
            'allowed_methods' => 'card,wallet',
            'display' => 'en',
            'expire_minutes' => 30,
            'enable_3ds' => true,
        ],
    ]);

    Http::fake([
        'https://test-api.kashier.io/v3/payment/sessions' => Http::response([
            'status' => 'CREATED',
            '_id' => 'session-paid-booking',
            'sessionUrl' => 'https://payments.kashier.io/session/session-paid-booking?mode=test',
            'paymentParams' => ['order' => 'PAY-BOOKING'],
        ]),
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ]);

    $response->assertRedirect('https://payments.kashier.io/session/session-paid-booking?mode=test');

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    $payment = $booking->payments()->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Processing)
        ->and($payment->amount_minor)->toBe(20000)
        ->and($payment->currency)->toBe('EGP')
        ->and($payment->checkout_url)->toContain('session-paid-booking');

    Http::assertSent(function ($request): bool {
        return $request['customer']['email'] === 'ahmed@example.com'
            && str_starts_with((string) $request['customer']['reference'], 'customer-')
            && $request['merchantRedirect'] === route('payments.kashier.return');
    });
});

test('online paid booking requires customer email', function (): void {
    $tenant = paidBookingTenant('paid-email-required');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
    ])->assertSessionHasErrors('booking');
});
