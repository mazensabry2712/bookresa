<?php

namespace App\Infrastructure\Payments\Kashier;

class KashierRedirectVerifier
{
    /**
     * @param array<string, mixed> $query
     */
    public function verify(array $query, string $apiKey): bool
    {
        $signature = trim((string) ($query['signature'] ?? ''));

        if ($signature === '' || $apiKey === '') {
            return false;
        }

        return hash_equals($this->sign($query, $apiKey), $signature);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function sign(array $query, string $apiKey): string
    {
        $fields = [
            'paymentStatus',
            'cardDataToken',
            'maskedCard',
            'merchantOrderId',
            'orderId',
            'cardBrand',
            'orderReference',
            'transactionId',
            'amount',
            'currency',
        ];

        $payload = implode('&', array_map(
            static fn (string $field): string => $field.'='.(array_key_exists($field, $query) && $query[$field] !== null
                ? (string) $query[$field]
                : 'null'),
            $fields,
        ));

        return hash_hmac('sha256', $payload, $apiKey);
    }
}
