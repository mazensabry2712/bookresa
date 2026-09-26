<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\PaymentWebhookEvent;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierWebhookVerifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function webhookTenant(string $slug): Tenant
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

function webhookBooking(Tenant $tenant): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(\App\Domain\Service\Actions\CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Webhook Customer',
        'phone' => '01000000777',
        'normalized_phone' => '201000000777',
    ]);

    $start = CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo');

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $start->utc(),
        'ends_at' => $start->addMinutes(30)->utc(),
        'block_ends_at' => $start->addMinutes(30)->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => BookingPaymentStatus::Unpaid,
        'booking_reference' => 'BR-WEBHOOK-001',
    ]);
}

function webhookPayment(Tenant $tenant, Booking $booking): Payment
{
    app(CurrentTenant::class)->set($tenant);

    return Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-WEBHOOK-001',
        'provider' => 'kashier',
        'provider_reference' => 'session-webhook-001',
        'amount_minor' => 25000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
    ]);
}

function webhookPayload(string $reference, string $transactionId = 'TX-WEBHOOK-001', string $status = 'SUCCESS'): array
{
    return [
        'event' => 'pay',
        'data' => [
            'merchantOrderId' => 'MERCHANT-001',
            'kashierOrderId' => 'KASHIER-001',
            'orderReference' => $reference,
            'transactionId' => $transactionId,
            'status' => $status,
            'method' => 'card',
            'creationDate' => '2026-09-21T01:00:00Z',
            'amount' => 250,
            'currency' => 'EGP',
            'transactionResponseCode' => '00',
            'channel' => 'online | e-commerce',
            'signatureKeys' => [
                'amount',
                'channel',
                'currency',
                'kashierOrderId',
                'merchantOrderId',
                'method',
                'orderReference',
                'status',
                'transactionId',
                'transactionResponseCode',
            ],
        ],
    ];
}

test('signed Kashier success webhook marks payment and booking as paid', function (): void {
    $tenant = webhookTenant('webhook-success');
    $booking = webhookBooking($tenant);
    $payment = webhookPayment($tenant, $booking);

    $payload = webhookPayload($payment->reference);
    $signature = app(KashierWebhookVerifier::class)->sign($payload['data'], 'api-key');

    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $this->postJson(route('webhooks.kashier'), $payload, [
        'x-kashier-signature' => $signature,
    ])->assertNoContent();

    app(CurrentTenant::class)->set($tenant);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($booking->fresh()->payment_status)->toBe(BookingPaymentStatus::Paid)
        ->and(PaymentWebhookEvent::query()->count())->toBe(1);
});

test('duplicate Kashier webhook is acknowledged without double processing', function (): void {
    $tenant = webhookTenant('webhook-duplicate');
    $booking = webhookBooking($tenant);
    $payment = webhookPayment($tenant, $booking);

    $payload = webhookPayload($payment->reference);
    $signature = app(KashierWebhookVerifier::class)->sign($payload['data'], 'api-key');
    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $headers = ['x-kashier-signature' => $signature];

    $this->postJson(route('webhooks.kashier'), $payload, $headers)->assertNoContent();
    $this->postJson(route('webhooks.kashier'), $payload, $headers)->assertStatus(409);

    app(CurrentTenant::class)->set($tenant);

    expect(PaymentWebhookEvent::query()->count())->toBe(1)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('invalid Kashier webhook signature is rejected', function (): void {
    $tenant = webhookTenant('webhook-invalid');
    $booking = webhookBooking($tenant);
    $payment = webhookPayment($tenant, $booking);

    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $this->postJson(route('webhooks.kashier'), webhookPayload($payment->reference), [
        'x-kashier-signature' => 'invalid-signature',
    ])->assertUnauthorized();

    app(CurrentTenant::class)->set($tenant);

    app(CurrentTenant::class)->set($tenant);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Processing)
        ->and(PaymentWebhookEvent::query()->count())->toBe(0);
});

test('Kashier webhook amount mismatch is rejected', function (): void {
    $tenant = webhookTenant('webhook-amount');
    $booking = webhookBooking($tenant);
    $payment = webhookPayment($tenant, $booking);

    $payload = webhookPayload($payment->reference);
    $payload['data']['amount'] = 999;

    $signature = app(KashierWebhookVerifier::class)->sign($payload['data'], 'api-key');
    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $this->postJson(route('webhooks.kashier'), $payload, [
        'x-kashier-signature' => $signature,
    ])->assertStatus(422);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Processing)
        ->and(PaymentWebhookEvent::query()->count())->toBe(0);
});

test('failed Kashier refund does not regress a paid payment', function (): void {
    $tenant = webhookTenant('webhook-refund-failure');
    $booking = webhookBooking($tenant);
    $payment = webhookPayment($tenant, $booking);
    $payment->forceFill(['status' => PaymentStatus::Paid])->save();

    $payload = webhookPayload($payment->reference, 'TX-REFUND-FAILURE', 'FAILURE');
    $payload['event'] = 'refund';

    $signature = app(KashierWebhookVerifier::class)->sign($payload['data'], 'api-key');
    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $this->postJson(route('webhooks.kashier'), $payload, [
        'x-kashier-signature' => $signature,
    ])->assertNoContent();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->metadata)->toMatchArray([
            'kashier_refund_status' => 'FAILURE',
        ]);
});
