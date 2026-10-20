<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-27 08:00:00', 'Africa/Cairo'));
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
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
            'payment_mode' => 'full',
            'customer_email_required' => false,
        ],
    ]);

    app(PlatformSettings::class)->put([
        'centralized_booking_payments_enabled' => '1',
        'commission_free_customer_limit' => '1000',
        'booking_commission_rate_basis_points' => '500',
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

    $response = $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
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
            && $request['merchantId'] === 'MID-123'
            && $request['merchantRedirect'] === route('payments.kashier.return')
            && $request['allowedMethods'] === 'card,wallet'
            && !array_key_exists('connectedAccount', $request->data());
    });
});

test('centralized booking checkout collects full price regardless of legacy workspace deposit settings', function (): void {
    $tenant = paidBookingTenant('deposit-clinic');

    $tenant->profile()->update([
        'booking_settings' => [
            'customer_account_required' => false,
            'payment_required' => true,
            'payment_mode' => 'deposit',
            'deposit_percent' => 25,
            'customer_email_required' => false,
        ],
    ]);

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
            '_id' => 'session-deposit-booking',
            'sessionUrl' => 'https://payments.kashier.io/session/session-deposit-booking?mode=test',
        ]),
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ]);

    $response->assertRedirect('https://payments.kashier.io/session/session-deposit-booking?mode=test');

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    $payment = $booking->payments()->firstOrFail();

    expect($payment->amount_minor)->toBe(20000)
        ->and($payment->status)->toBe(PaymentStatus::Processing);
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

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
    ])->assertSessionHasErrors('email');
});

test('public booking allows cash at the center even when online payments are enabled', function (): void {
    $tenant = paidBookingTenant('cash-choice-clinic');
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    Http::fake();
    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'cash',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
    ]);

    $response->assertRedirect();

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    expect($booking->payment_method)->toBe('cash')
        ->and($booking->payment_status->value)->toBe('unpaid')
        ->and($booking->payments()->count())->toBe(0);

    Http::assertNothingSent();
});

test('online booking choice is rejected when platform online payments are disabled', function (): void {
    $tenant = paidBookingTenant('online-disabled-clinic');
    app(PlatformSettings::class)->put(['centralized_booking_payments_enabled' => '0']);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ])->assertSessionHasErrors('booking');

    expect(Booking::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(0);
});

test('public booking page shows both payment choices when online payments are enabled', function (): void {
    $tenant = paidBookingTenant('payment-choice-page');
    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.canonical.show', $tenant->slug))
        ->assertOk()
        ->assertSee('name="payment_method" value="cash"', false)
        ->assertSee('name="payment_method" value="online"', false)
        ->assertSee('Pay online')
        ->assertSee('Cash when you arrive at the center')
        ->assertSee('data-summary-payment-method', false);
});

test('failed online payment session creation cancels the pending booking and releases the slot', function (): void {
    $tenant = paidBookingTenant('checkout-failure');
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
            'message' => 'Provider unavailable',
        ], 503),
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ]);

    $response->assertSessionHasErrors('booking');

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    expect($booking->status)->toBe(BookingStatus::Cancelled)
        ->and($booking->statusHistory()->where('to_status', BookingStatus::Cancelled->value)->exists())->toBeTrue();

    $slots = app(AvailabilityService::class)->slots(
        $service,
        CarbonImmutable::parse('2026-09-28 00:00:00', 'Africa/Cairo'),
    );

    expect(collect($slots)->contains(
        fn (array $slot): bool => $slot['start']->format('H:i') === '10:00',
    ))->toBeTrue();
});

test('expired online booking checkout can start a new payment attempt from the signed confirmation page', function (): void {
    $tenant = paidBookingTenant('payment-retry-page');
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
        'https://test-api.kashier.io/v3/payment/sessions' => Http::sequence()
            ->push([
                'status' => 'CREATED',
                '_id' => 'session-retry-initial',
                'sessionUrl' => 'https://payments.kashier.io/session/session-retry-initial?mode=test',
            ])
            ->push([
                'status' => 'CREATED',
                '_id' => 'session-retry-second',
                'sessionUrl' => 'https://payments.kashier.io/session/session-retry-second?mode=test',
            ]),
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.canonical.store', $tenant->slug), [
        'payment_method' => 'online',
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Retry Customer',
        'phone' => '+20 100 123 4999',
        'email' => 'retry@example.com',
    ])->assertRedirect('https://payments.kashier.io/session/session-retry-initial?mode=test');

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();
    $initialPayment = Payment::withoutGlobalScopes()
        ->where('payable_type', $booking->getMorphClass())
        ->where('payable_id', $booking->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    $initialPayment->forceFill(['expires_at' => CarbonImmutable::now('UTC')->subMinute()])->save();

    $retryUrl = URL::temporarySignedRoute(
        'public.booking.canonical.retry-payment',
        now()->addHour(),
        ['tenant' => $tenant->slug, 'booking' => $booking->booking_reference],
    );

    $this->post($retryUrl)
        ->assertRedirect('https://payments.kashier.io/session/session-retry-second?mode=test');

    $payments = Payment::withoutGlobalScopes()
        ->where('payable_type', $booking->getMorphClass())
        ->where('payable_id', $booking->id)
        ->orderBy('id')
        ->get();

    expect($payments)->toHaveCount(2)
        ->and($payments->last()->status)->toBe(PaymentStatus::Processing)
        ->and($payments->last()->provider_reference)->toBe('session-retry-second')
        ->and(Booking::withoutGlobalScopes()->findOrFail($booking->id)->status)->toBe(BookingStatus::Pending);
});
