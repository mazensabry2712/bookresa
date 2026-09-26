<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\StartBookingPayment;
use Tests\Fakes\FakePaymentGateway;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function paymentTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

function paymentBooking(Tenant $tenant, string $reference = 'BR-PAY-001'): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Payment Customer',
        'phone' => '01000000888',
        'normalized_phone' => '201000000888',
    ]);

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-09-28 10:30', 'Africa/Cairo')->utc(),
        'block_ends_at' => CarbonImmutable::parse('2026-09-28 10:30', 'Africa/Cairo')->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

test('payment service persists a provider-neutral payment and gateway result', function (): void {
    $tenant = paymentTenant('payment-core');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $payment = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'egp',
        'fake',
        'Booking payment',
        ['source' => 'test'],
        'booking-payment-1',
    );

    expect($payment->status)->toBe(PaymentStatus::Processing)
        ->and($payment->amount_minor)->toBe(25000)
        ->and($payment->currency)->toBe('EGP')
        ->and($payment->provider)->toBe('fake')
        ->and($payment->provider_reference)->toBe('FAKE-'.$payment->reference)
        ->and($payment->metadata)->toMatchArray(['source' => 'test', 'gateway' => 'fake'])
        ->and($payment->payable->is($booking))->toBeTrue();
});

test('idempotency key returns the existing payment without creating another gateway payment', function (): void {
    $tenant = paymentTenant('payment-idempotent');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $first = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: 'duplicate-key',
    );

    $second = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: 'duplicate-key',
    );

    expect($second->is($first))->toBeTrue()
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

test('an incomplete idempotent payment can retry provider creation', function (): void {
    $tenant = paymentTenant('payment-retry');
    $booking = paymentBooking($tenant);

    $gateway = new class implements PaymentGateway {
        public int $createCalls = 0;

        public function createPayment(PaymentRequest $request): PaymentGatewayResult
        {
            $this->createCalls++;

            if ($this->createCalls === 1) {
                throw new RuntimeException('Temporary gateway failure.');
            }

            return new PaymentGatewayResult(
                status: PaymentStatus::Processing,
                providerReference: 'FAKE-RETRY-'.$request->merchantReference,
                checkoutUrl: 'https://payments.example.test/retry/'.$request->merchantReference,
            );
        }

        public function verifyPayment(string $providerReference): PaymentGatewayResult
        {
            return new PaymentGatewayResult(status: PaymentStatus::Paid, providerReference: $providerReference);
        }

        public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
        {
            return new PaymentGatewayResult(status: PaymentStatus::Refunded, providerReference: $providerReference);
        }
    };

    expect(fn () => app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: 'retryable-payment',
    ))->toThrow(RuntimeException::class);

    $pending = Payment::query()->where('idempotency_key', 'retryable-payment')->firstOrFail();

    expect($pending->status)->toBe(PaymentStatus::Pending)
        ->and($pending->provider_reference)->toBeNull();

    $retried = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: 'retryable-payment',
    );

    expect($retried->status)->toBe(PaymentStatus::Processing)
        ->and($retried->provider_reference)->toStartWith('FAKE-RETRY-')
        ->and($gateway->createCalls)->toBe(2)
        ->and(Payment::query()->count())->toBe(1);
});

test('booking payment creates a new attempt after a failed payment', function (): void {
    $tenant = paymentTenant('booking-payment-retry');
    $booking = paymentBooking($tenant);
    $booking->customer()->update(['email' => 'customer@example.com']);

    $gateway = new FakePaymentGateway();

    $this->app->instance(PaymentGateway::class, $gateway);
    config(['bookresa.payments.default_provider' => 'fake']);

    $first = app(StartBookingPayment::class)->handle($booking);
    $first->forceFill(['status' => PaymentStatus::Failed])->save();

    $second = app(StartBookingPayment::class)->handle($booking->fresh());

    expect($first->id)->not->toBe($second->id)
        ->and($first->idempotency_key)->toBe('booking-'.$booking->id.'-fake-attempt-1')
        ->and($second->idempotency_key)->toBe('booking-'.$booking->id.'-fake-attempt-2')
        ->and($gateway->createCalls)->toBe(2)
        ->and(Payment::query()->count())->toBe(2);
});

test('verified gateway result can move payment to paid and paid payment cannot regress', function (): void {
    $tenant = paymentTenant('payment-verify');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $payment = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
    );

    $payment = app(PaymentService::class)->applyResult(
        $payment,
        $gateway->verifyPayment($payment->provider_reference),
    );

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull();

    $payment = app(PaymentService::class)->applyResult(
        $payment,
        new PaymentGatewayResult(status: PaymentStatus::Failed),
    );

    expect($payment->status)->toBe(PaymentStatus::Paid);
});

test('payment refund transition is supported after payment is paid', function (): void {
    $tenant = paymentTenant('payment-refund');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $payment = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
    );

    $payment = app(PaymentService::class)->applyResult(
        $payment,
        $gateway->verifyPayment($payment->provider_reference),
    );

    $payment = app(PaymentService::class)->applyResult(
        $payment,
        $gateway->refundPayment($payment->provider_reference, 25000),
    );

    expect($payment->status)->toBe(PaymentStatus::Refunded);
});

test('payment payable must belong to the current tenant', function (): void {
    $tenantA = paymentTenant('payment-tenant-a');
    $booking = paymentBooking($tenantA);

    $tenantB = paymentTenant('payment-tenant-b');
    $gateway = new FakePaymentGateway();

    expect(fn () => app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
    ))->toThrow(LogicException::class);
});

test('idempotency key cannot be reused for a different payment payload', function (): void {
    $tenant = paymentTenant('payment-idempotency-mismatch');
    $booking = paymentBooking($tenant, 'BR-PAY-MISMATCH');
    $gateway = new FakePaymentGateway();

    app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: 'same-key',
    );

    expect(fn () => app(PaymentService::class)->start(
        $gateway,
        $booking,
        30000,
        'EGP',
        'fake',
        idempotencyKey: 'same-key',
    ))->toThrow(RuntimeException::class);
});

test('booking exposes its payments', function (): void {
    $tenant = paymentTenant('payment-booking-relation');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $payment = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
    );

    expect($booking->payments()->first()?->is($payment))->toBeTrue();
});

test('pending idempotent payment is not sent to the provider twice', function (): void {
    $tenant = paymentTenant('payment-concurrent-pending');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    $payment = Payment::query()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-PENDING-001',
        'provider' => 'fake',
        'amount_minor' => 25000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Pending,
        'idempotency_key' => 'pending-key',
    ]);

    $result = app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'EGP',
        'fake',
        idempotencyKey: $payment->idempotency_key,
    );

    expect($result->id)->toBe($payment->id)
        ->and($result->status)->toBe(PaymentStatus::Pending)
        ->and($gateway->createCalls)->toBe(0);
});

test('expired booking payment session creates a fresh attempt', function (): void {
    $tenant = paymentTenant('payment-expired-session');
    $booking = paymentBooking($tenant);
    $booking->customer()->update(['email' => 'customer@example.com']);

    $expired = Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-EXPIRED-001',
        'provider' => 'fake',
        'provider_reference' => 'expired-session',
        'amount_minor' => 25000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
        'checkout_url' => 'https://payments.example.test/expired',
        'expires_at' => now()->subMinute(),
        'idempotency_key' => 'booking-'.$booking->id.'-fake-attempt-1',
    ]);

    $gateway = new FakePaymentGateway();
    $this->app->instance(PaymentGateway::class, $gateway);
    config(['bookresa.payments.default_provider' => 'fake']);

    $fresh = app(StartBookingPayment::class)->handle($booking->fresh());

    expect($fresh->id)->not->toBe($expired->id)
        ->and($fresh->status)->toBe(PaymentStatus::Processing)
        ->and($fresh->idempotency_key)->toBe('booking-'.$booking->id.'-fake-attempt-2')
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(2);
});

test('payment amount and currency are validated', function (): void {
    $tenant = paymentTenant('payment-validation');
    $booking = paymentBooking($tenant);
    $gateway = new FakePaymentGateway();

    expect(fn () => app(PaymentService::class)->start(
        $gateway,
        $booking,
        0,
        'EGP',
        'fake',
    ))->toThrow(RuntimeException::class);

    expect(fn () => app(PaymentService::class)->start(
        $gateway,
        $booking,
        25000,
        'Egyptian Pound',
        'fake',
    ))->toThrow(RuntimeException::class);
});
