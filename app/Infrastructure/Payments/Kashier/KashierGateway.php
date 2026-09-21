<?php

namespace App\Infrastructure\Payments\Kashier;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class KashierGateway implements PaymentGateway
{
    public function createPayment(PaymentRequest $request): PaymentGatewayResult
    {
        $config = config('bookresa.payments.kashier');
        $this->assertConfigured($config);

        $merchantRedirect = $request->metadata['merchant_redirect'] ?? $config['merchant_redirect'];
        $customerEmail = $request->metadata['customer_email'] ?? null;
        $customerReference = $request->metadata['customer_reference'] ?? null;

        if (blank($merchantRedirect)) {
            throw new RuntimeException('Kashier merchant redirect URL is required.');
        }

        if (blank($customerEmail) || blank($customerReference)) {
            throw new RuntimeException('Kashier customer email and reference are required.');
        }

        $payload = [
            'expireAt' => CarbonImmutable::now('UTC')
                ->addMinutes((int) $config['expire_minutes'])
                ->toIso8601String(),
            'maxFailureAttempts' => (int) $config['max_failure_attempts'],
            'paymentType' => 'credit',
            'amount' => number_format($request->amountMinor / 100, 2, '.', ''),
            'currency' => $request->currency,
            'order' => $request->merchantReference,
            'merchantId' => $config['merchant_id'],
            'merchantRedirect' => $merchantRedirect,
            'customer' => [
                'email' => $customerEmail,
                'reference' => $customerReference,
            ],
            'display' => $config['display'],
            'type' => 'one-time',
            'allowedMethods' => $config['allowed_methods'],
            'failureRedirect' => false,
            'description' => $request->description,
            'interactionSource' => 'ECOMMERCE',
            'enable3DS' => (bool) $config['enable_3ds'],
        ];

        if ($config['server_webhook']) {
            $payload['serverWebhook'] = $config['server_webhook'];
        }

        if ($request->metadata !== []) {
            $payload['metaData'] = $request->metadata;
        }

        $response = $this->client()
            ->withHeaders([
                'Authorization' => $config['secret_key'],
                'api-key' => $config['api_key'],
            ])
            ->post('/v3/payment/sessions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Kashier payment session creation failed: '.$response->body());
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['sessionUrl'], $data['_id'])) {
            throw new RuntimeException('Kashier returned an invalid payment session response.');
        }

        return new PaymentGatewayResult(
            status: PaymentStatus::Processing,
            providerReference: (string) $data['_id'],
            checkoutUrl: (string) $data['sessionUrl'],
            metadata: [
                'session_id' => (string) $data['_id'],
                'merchant_order' => data_get($data, 'paymentParams.order'),
                'merchant_id' => $config['merchant_id'],
            ],
        );
    }

    public function verifyPayment(string $providerReference): PaymentGatewayResult
    {
        $config = config('bookresa.payments.kashier');
        $this->assertConfigured($config);

        $response = $this->client()
            ->withHeaders(['Authorization' => $config['secret_key']])
            ->get('/v3/payment/sessions/'.rawurlencode($providerReference).'/payment');

        if ($response->failed()) {
            throw new RuntimeException('Kashier payment verification failed: '.$response->body());
        }

        $data = $response->json('data', []);

        if (! is_array($data)) {
            throw new RuntimeException('Kashier returned an invalid payment verification response.');
        }

        $status = match (strtoupper((string) ($data['status'] ?? 'PENDING'))) {
            'PAID', 'AUTHORIZED' => PaymentStatus::Paid,
            'FAILED', 'EXPIRED', 'ABANDONED', 'REJECTED', 'VOIDED', 'REVERSED' => PaymentStatus::Failed,
            'REFUNDED' => PaymentStatus::Refunded,
            default => PaymentStatus::Processing,
        };

        return new PaymentGatewayResult(
            status: $status,
            providerReference: $providerReference,
            method: isset($data['method']) ? (string) $data['method'] : null,
            metadata: [
                'kashier_status' => $data['status'] ?? null,
                'merchant_order_id' => $data['merchantOrderId'] ?? null,
                'kashier_order_id' => $data['orderId'] ?? null,
                'session_id' => $data['sessionId'] ?? $providerReference,
            ],
            paidAt: $status === PaymentStatus::Paid
                ? $this->parseDate($data['updatedAt'] ?? null)
                : null,
        );
    }

    public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
    {
        if ($amountMinor <= 0) {
            throw new RuntimeException('Refund amount must be greater than zero.');
        }

        $config = config('bookresa.payments.kashier');
        $this->assertConfigured($config);

        $lookup = $this->client()
            ->withHeaders(['Authorization' => $config['secret_key']])
            ->get('/v3/payment/sessions/'.rawurlencode($providerReference).'/payment');

        if ($lookup->failed()) {
            throw new RuntimeException('Kashier payment lookup before refund failed: '.$lookup->body());
        }

        $data = $lookup->json('data', []);

        if (! is_array($data)) {
            throw new RuntimeException('Kashier returned an invalid payment lookup response.');
        }

        $orderId = $data['merchantOrderId'] ?? $data['orderId'] ?? null;

        if (! $orderId) {
            throw new RuntimeException('Kashier order identifier is missing for refund.');
        }

        $response = $this->client()
            ->withHeaders(['Authorization' => $config['secret_key']])
            ->put('/v3/payment/refund/'.rawurlencode((string) $orderId), [
                'apiOperation' => 'REFUND',
                'transaction' => [
                    'amount' => number_format($amountMinor / 100, 2, '.', ''),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Kashier refund failed: '.$response->body());
        }

        $status = match (strtoupper((string) ($response->json('status') ?? 'PENDING'))) {
            'SUCCESS' => PaymentStatus::Refunded,
            'FAILURE' => PaymentStatus::Failed,
            default => PaymentStatus::Processing,
        };

        return new PaymentGatewayResult(
            status: $status,
            providerReference: $providerReference,
            metadata: [
                'kashier_refund_status' => $response->json('status'),
                'refunded_amount_minor' => $amountMinor,
            ],
        );
    }

    private function client(): PendingRequest
    {
        $config = config('bookresa.payments.kashier');

        return Http::baseUrl(rtrim((string) $config['base_url'], '/'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 250);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function assertConfigured(array $config): void
    {
        foreach (['merchant_id', 'api_key', 'secret_key'] as $key) {
            if (blank($config[$key] ?? null)) {
                throw new RuntimeException('Kashier is not configured: '.$key.'.');
            }
        }
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
