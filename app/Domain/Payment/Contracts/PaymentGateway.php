<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;

interface PaymentGateway
{
    public function createPayment(PaymentRequest $request): PaymentGatewayResult;

    public function verifyPayment(string $providerReference): PaymentGatewayResult;

    public function refundPayment(
        string $providerReference,
        int $amountMinor,
    ): PaymentGatewayResult;
}
