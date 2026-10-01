<?php

use App\Infrastructure\Payments\Kashier\KashierRedirectVerifier;

test('Kashier redirect verifier signs the documented fixed parameter order', function (): void {
    $query = [
        'paymentStatus' => 'SUCCESS',
        'cardDataToken' => 'token',
        'maskedCard' => '512345******2346',
        'merchantOrderId' => 'MERCHANT-1',
        'orderId' => 'ORDER-1',
        'cardBrand' => 'Mastercard',
        'orderReference' => 'PAY-1',
        'transactionId' => 'TX-1',
        'amount' => '100',
        'currency' => 'EGP',
    ];

    $verifier = app(KashierRedirectVerifier::class);
    $signature = $verifier->sign($query, 'api-key');

    expect($verifier->verify([...$query, 'signature' => $signature], 'api-key'))->toBeTrue()
        ->and($verifier->verify([...$query, 'signature' => 'invalid'], 'api-key'))->toBeFalse();
});

test('Kashier redirect verifier uses literal null for absent fields', function (): void {
    $query = [
        'paymentStatus' => 'FAILURE',
        'merchantOrderId' => 'MERCHANT-1',
        'orderId' => 'ORDER-1',
        'orderReference' => 'PAY-1',
        'transactionId' => 'TX-1',
        'amount' => '100',
        'currency' => 'EGP',
    ];

    $verifier = app(KashierRedirectVerifier::class);
    $signature = $verifier->sign($query, 'api-key');

    expect($verifier->verify([...$query, 'signature' => $signature], 'api-key'))->toBeTrue();
});
