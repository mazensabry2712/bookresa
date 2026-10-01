<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use App\Infrastructure\Payments\Kashier\KashierRedirectVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

test('signed Kashier return verifies the payment server-side and redirects to confirmation', function (): void {
    $tenant = Tenant::query()->create([
        'slug' => 'return-clinic',
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Return Clinic', 'ar' => 'Return Clinic'],
        'timezone' => 'Africa/Cairo',
    ]);

    $service = \App\Domain\Service\Actions\CreateService::class;
    $service = app($service)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Ahmed',
        'phone' => '01000000999',
        'normalized_phone' => '201000000999',
        'email' => 'ahmed@example.com',
    ]);

    $booking = Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDay()->setTime(10, 0)->utc(),
        'ends_at' => now()->addDay()->setTime(10, 30)->utc(),
        'block_ends_at' => now()->addDay()->setTime(10, 30)->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => BookingPaymentStatus::Unpaid,
        'booking_reference' => 'BR-RETURN-001',
    ]);

    $payment = Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-RETURN-001',
        'provider' => 'kashier',
        'provider_reference' => 'session-return-001',
        'amount_minor' => 20000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
        'checkout_url' => 'https://payments.kashier.io/session/session-return-001?mode=test',
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

    $query = [
        'paymentStatus' => 'SUCCESS',
        'cardDataToken' => 'token',
        'maskedCard' => '512345******2346',
        'merchantOrderId' => 'merchant-return-001',
        'orderId' => 'order-return-001',
        'cardBrand' => 'Mastercard',
        'orderReference' => $payment->reference,
        'transactionId' => 'TX-RETURN-001',
        'amount' => '200',
        'currency' => 'EGP',
        'mode' => 'test',
    ];

    $query['signature'] = app(KashierRedirectVerifier::class)->sign($query, 'api-key');

    Http::fake([
        'https://test-api.kashier.io/v3/payment/sessions/session-return-001/payment' => Http::response([
            'data' => [
                'sessionId' => 'session-return-001',
                'status' => 'PAID',
                'method' => 'card',
                'updatedAt' => '2026-09-21T01:00:00Z',
                'merchantOrderId' => $payment->reference,
                'orderId' => 'order-return-001',
            ],
        ]),
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->get(route('payments.kashier.return').'?'.http_build_query($query));

    $payment = Payment::withoutGlobalScopes()->findOrFail($payment->id);
    $booking = Booking::withoutGlobalScopes()->findOrFail($booking->id);

    $response->assertRedirectToRoute('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]);

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($booking->payment_status)->toBe(BookingPaymentStatus::Paid);
});

test('invalid Kashier return signature is rejected before payment lookup changes anything', function (): void {
    $tenant = Tenant::query()->create([
        'slug' => 'return-invalid-clinic',
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Return Invalid Clinic', 'ar' => 'Return Invalid Clinic'],
        'timezone' => 'Africa/Cairo',
    ]);

    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    app(CurrentTenant::class)->clear();

    $this->get(route('payments.kashier.return').'?'.http_build_query([
        'orderReference' => 'PAY-UNKNOWN',
        'paymentStatus' => 'SUCCESS',
        'amount' => '200',
        'currency' => 'EGP',
        'signature' => 'invalid',
    ]))->assertUnauthorized();
});
