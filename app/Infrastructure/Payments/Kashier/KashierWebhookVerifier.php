<?php

namespace App\Infrastructure\Payments\Kashier;

class KashierWebhookVerifier
{
    /**
     * @param array<string, mixed> $data
     */
    public function verify(array $data, string $signature, string $apiKey): bool
    {
        if ($signature === '' || $apiKey === '') {
            return false;
        }

        $expected = $this->sign($data, $apiKey);

        return hash_equals(strtolower($expected), strtolower(trim($signature)));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sign(array $data, string $apiKey): string
    {
        $keys = array_values(array_unique(array_map(
            static fn ($key): string => (string) $key,
            is_array($data['signatureKeys'] ?? null) ? $data['signatureKeys'] : [],
        )));

        sort($keys, SORT_STRING);

        $payload = collect($keys)
            ->map(fn (string $key): string => $key.'='.rawurlencode((string) ($data[$key] ?? '')))
            ->implode('&');

        return hash_hmac('sha256', $payload, $apiKey);
    }
}
