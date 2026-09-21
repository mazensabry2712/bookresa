<?php

namespace App\Domain\Payment\Data;

final readonly class PaymentRequest
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $merchantReference,
        public int $amountMinor,
        public string $currency,
        public ?string $description = null,
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {
    }
}
