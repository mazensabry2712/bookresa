<?php

use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use Illuminate\Support\Str;

test('Kashier sandbox API can create and read a real payment session', function (): void {
    if (filter_var(env('KASHIER_E2E', false), FILTER_VALIDATE_BOOL) === false) {
        $this->markTestSkipped('Set KASHIER_E2E=true to run the live Kashier sandbox smoke test.');
    }

    if ((string) config('bookresa.payments.kashier.mode', 'test') !== 'test') {
        throw new RuntimeException('Kashier sandbox smoke test must run with KASHIER_MODE=test.');
    }

    $merchantRedirect = (string) config('bookresa.payments.kashier.merchant_redirect');
    $customerEmail = trim((string) env('KASHIER_E2E_CUSTOMER_EMAIL'));
    $customerReference = trim((string) env('KASHIER_E2E_CUSTOMER_REFERENCE'));

    expect($merchantRedirect)->not->toBe('')
        ->and($customerEmail)->not->toBe('')
        ->and($customerReference)->not->toBe('');

    $reference = 'BOOKRESA-E2E-'.Str::upper(Str::random(16));

    $result = app(KashierGateway::class)->createPayment(new PaymentRequest(
        merchantReference: $reference,
        amountMinor: 100,
        currency: 'EGP',
        description: 'BookResa Kashier sandbox smoke test',
        metadata: [
            'merchant_redirect' => $merchantRedirect,
            'customer_email' => $customerEmail,
            'customer_reference' => $customerReference,
        ],
    ));

    expect($result->status)->toBe(PaymentStatus::Processing)
        ->and($result->providerReference)->not->toBe('')
        ->and($result->checkoutUrl)->toStartWith('https://payments.kashier.io/');

    $verification = app(KashierGateway::class)->verifyPayment($result->providerReference);

    expect($verification->providerReference)->toBe($result->providerReference)
        ->and($verification->status)->toBeIn([
            PaymentStatus::Processing,
            PaymentStatus::Paid,
            PaymentStatus::Failed,
            PaymentStatus::Refunded,
        ]);
});