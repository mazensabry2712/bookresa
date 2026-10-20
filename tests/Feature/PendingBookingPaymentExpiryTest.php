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

function expiredCheckoutTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Expired Checkout Clinic', 'ar' => 'عيادة اختبار الدفع المنتهي'],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

function expiredCheckoutBooking(Tenant $tenant, string $reference): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $customer = Customer::query()->create([
        'name' => 'Expired Checkout Customer',
        'phone' => '01019990000',
        'normalized_phone' => '201019990000',
        'email' => 'expired@example.com',
    ]);
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    return Booking::query()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => CarbonImmutable::now('Africa/Cairo')->addDay()->setTime(10, 0)->utc(),
        'ends_at' => CarbonImmutable::now('Africa/Cairo')->addDay()->setTime(10, 30)->utc(),
        'block_ends_at' => CarbonImmutable::now('Africa/Cairo')->addDay()->setTime(10, 30)->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'payment_method' => 'online',
        'booking_reference' => $reference,
    ]);
}

function expiredCheckoutPayment(Booking $booking, PaymentStatus $status, string $reference): Payment
{
    app(CurrentTenant::class)->set(Tenant::query()->findOrFail($booking->tenant_id));

    return Payment::query()->create([
        'tenant_id' => $booking->tenant_id,
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => $reference,
        'provider' => 'kashier',
        'provider_reference' => 'SESSION-'.$reference,
        'amount_minor' => 20000,
        'currency' => 'EGP',
        'status' => $status,
        'checkout_url' => 'https://payments.kashier.io/session/SESSION-'.$reference,
        'expires_at' => CarbonImmutable::now('UTC')->subMinutes(5),
        'metadata' => ['centralized_payment' => true],
    ]);
}

function expiryTestGateway(PaymentStatus $status): PaymentGateway
{
    return new class($status) implements PaymentGateway
    {
        public int $verifyCalls = 0;

        public function __construct(private readonly PaymentStatus $status)
        {
        }

        public function createPayment(PaymentRequest $request): PaymentGatewayResult
        {
            throw new RuntimeException('This test gateway should not create a payment.');
        }

        public function verifyPayment(string $providerReference): PaymentGatewayResult
        {
            $this->verifyCalls++;

            $reference = str_starts_with($providerReference, 'SESSION-PAY-')
                ? substr($providerReference, strlen('SESSION-'))
                : '';

            return new PaymentGatewayResult(
                status: $this->status,
                providerReference: $providerReference,
                metadata: [
                    'merchant_id' => 'MID-EXPIRY',
                    'merchant_order_id' => $reference,
                    'amount' => '200.00',
                    'currency' => 'EGP',
                ],
            );
        }

        public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
        {
            throw new RuntimeException('Refund is not used in this test.');
        }
    };
}

beforeEach(function (): void {
    config([
        'bookresa.payments.default_provider' => 'kashier',
        'bookresa.payments.kashier' => [
            'mode' => 'test',
            'base_url' => 'https://test-api.kashier.io',
            'merchant_id' => 'MID-EXPIRY',
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
});

test('expired checkout only releases a pending booking after provider verification confirms failure', function (): void {
    $tenant = expiredCheckoutTenant('expire-failed-payment');
    $booking = expiredCheckoutBooking($tenant, 'BR-EXPIRE-FAILED');
    $payment = expiredCheckoutPayment($booking, PaymentStatus::Processing, 'PAY-EXPIRE-FAILED');
    $gateway = expiryTestGateway(PaymentStatus::Failed);
    $this->app->instance(PaymentGateway::class, $gateway);

    app(CurrentTenant::class)->clear();

    $this->artisan('bookresa:expire-pending-booking-payments')->assertSuccessful();

    app(CurrentTenant::class)->set($tenant);

    $freshBooking = Booking::withoutGlobalScopes()->findOrFail($booking->id);
    $freshPayment = Payment::withoutGlobalScopes()->findOrFail($payment->id);
    $lastHistory = $freshBooking->statusHistory()->latest('id')->first();

    expect($gateway->verifyCalls)->toBe(1)
        ->and($freshPayment->status)->toBe(PaymentStatus::Failed)
        ->and($freshBooking->status)->toBe(BookingStatus::Cancelled)
        ->and($lastHistory?->reason)->toBe(Booking::EXPIRED_ONLINE_PAYMENT_CANCELLATION_REASON);
});

test('expired checkout keeps the booking slot when provider verification confirms payment', function (): void {
    $tenant = expiredCheckoutTenant('expire-paid-payment');
    $booking = expiredCheckoutBooking($tenant, 'BR-EXPIRE-PAID');
    $payment = expiredCheckoutPayment($booking, PaymentStatus::Processing, 'PAY-EXPIRE-PAID');
    $gateway = expiryTestGateway(PaymentStatus::Paid);
    $this->app->instance(PaymentGateway::class, $gateway);

    app(CurrentTenant::class)->clear();

    $this->artisan('bookresa:expire-pending-booking-payments')->assertSuccessful();

    app(CurrentTenant::class)->set($tenant);

    $freshBooking = Booking::withoutGlobalScopes()->findOrFail($booking->id);
    $freshPayment = Payment::withoutGlobalScopes()->findOrFail($payment->id);

    expect($gateway->verifyCalls)->toBe(1)
        ->and($freshPayment->status)->toBe(PaymentStatus::Paid)
        ->and($freshBooking->payment_status->value)->toBe('paid')
        ->and($freshBooking->status)->toBe(BookingStatus::Pending)
        ->and($freshBooking->statusHistory()->where('to_status', BookingStatus::Cancelled->value)->exists())->toBeFalse();
});
