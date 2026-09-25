<?php

namespace App\Domain\Payment\Data;

use App\Domain\Payment\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final readonly class PaymentGatewayResult
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public PaymentStatus $status,
        public ?string $providerReference = null,
        public ?string $checkoutUrl = null,
        public ?string $method = null,
        public array $metadata = [],
        public ?CarbonImmutable $paidAt = null,
    ) {
    }
}
