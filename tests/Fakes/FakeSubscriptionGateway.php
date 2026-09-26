<?php

namespace Tests\Fakes;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;

final class FakeSubscriptionGateway implements PaymentGateway
{
    public int $createCalls = 0;

    public function createPayment(PaymentRequest $request): PaymentGatewayResult
    {
        $this->createCalls++;

        return new PaymentGatewayResult(
            status: PaymentStatus::Processing,
            providerReference: 'KASHIER-SESSION-'.str_pad((string) $this->createCalls, 3, '0', STR_PAD_LEFT),
            checkoutUrl: 'https://payments.example.test/session/'.str_pad((string) $this->createCalls, 3, '0', STR_PAD_LEFT),
            metadata: ['test_context' => 'subscription'],
        );
    }

    public function verifyPayment(string $providerReference): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Paid,
            providerReference: $providerReference,
        );
    }

    public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Refunded,
            providerReference: $providerReference,
        );
    }
}
