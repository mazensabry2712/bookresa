<?php

use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function kashierConfig(array $overrides = []): void
{
    config([
        'bookresa.payments.kashier' => array_merge([
            'mode' => 'test',
            'base_url' => 'https://test-api.kashier.io',
            'merchant_id' => 'MID-123',
            'api_key' => 'api-key',
            'secret_key' => 'secret-key',
            'merchant_redirect' => 'https://example.test/payment/return',
            'server_webhook' => 'https://example.test/kashier/webhook',
            'max_failure_attempts' => 3,
            'allowed_methods' => 'card,wallet',
            'display' => 'en',
            'expire_minutes' => 30,
            'enable_3ds' => true,
        ], $overrides),
    ]);
}

test('Kashier gateway creates a hosted payment session', function (): void {
    kashierConfig();

    Http::fake([
        'https://test-api.kashier.io/v3/payment/sessions' => Http::response([
            'status' => 'CREATED',
            '_id' => 'session-123',
            'sessionUrl' => 'https://payments.kashier.io/session/session-123?mode=test',
            'paymentParams' => ['order' => 'PAY-ABC1234567'],
        ], 200),
    ]);

    $result = app(KashierGateway::class)->createPayment(new PaymentRequest(
        merchantReference: 'PAY-ABC1234567',
        amountMinor: 25000,
        currency: 'EGP',
        description: 'Booking payment',
    ));

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://test-api.kashier.io/v3/payment/sessions'
            && $request->header('api-key')[0] === 'api-key'
            && $request->header('Authorization')[0] === 'secret-key'
            && $request['amount'] === '250.00'
            && $request['currency'] === 'EGP'
            && $request['order'] === 'PAY-ABC1234567'
            && $request['merchantId'] === 'MID-123';
    });

    expect($result->status)->toBe(PaymentStatus::Processing)
        ->and($result->providerReference)->toBe('session-123')
        ->and($result->checkoutUrl)->toContain('session-123');
});

test('Kashier gateway verifies a payment session', function (): void {
    kashierConfig();

    Http::fake([
        'https://test-api.kashier.io/v3/payment/sessions/session-123/payment' => Http::response([
            'data' => [
                'sessionId' => 'session-123',
                'status' => 'PAID',
                'method' => 'card',
                'updatedAt' => '2026-09-21T01:00:00Z',
                'merchantOrderId' => 'PAY-ABC1234567',
            ],
        ]),
    ]);

    $result = app(KashierGateway::class)->verifyPayment('session-123');

    expect($result->status)->toBe(PaymentStatus::Paid)
        ->and($result->providerReference)->toBe('session-123')
        ->and($result->method)->toBe('card')
        ->and($result->paidAt)->not->toBeNull();
});

test('Kashier gateway rejects an unconfigured integration', function (): void {
    kashierConfig([
        'api_key' => null,
    ]);

    expect(fn () => app(KashierGateway::class)->verifyPayment('session-123'))
        ->toThrow(RuntimeException::class);
});
