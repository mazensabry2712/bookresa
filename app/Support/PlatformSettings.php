<?php

namespace App\Support;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

final class PlatformSettings
{
    private const CACHE_KEY = 'bookresa.platform_settings';

    public function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => PlatformSetting::query()
                ->pluck('value', 'key')
                ->all(),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function kashierConfig(): array
    {
        $settings = $this->all();
        $config = (array) config('bookresa.payments.kashier', []);

        // Preserve explicit config values for tests and pre-integration installs.
        // Once Super Admin saves credentials, the database-backed live config wins.
        if (! array_key_exists('kashier_mode', $settings)) {
            return $config;
        }

        $config['mode'] = 'live';
        $config['base_url'] = 'https://api.kashier.io';
        $config['merchant_id'] = (string) ($settings['kashier_merchant_id'] ?? '');
        $config['api_key'] = $this->decryptStoredSecret('kashier_api_key_encrypted') ?? '';
        $config['secret_key'] = $this->decryptStoredSecret('kashier_secret_key_encrypted') ?? '';
        $config['server_webhook'] = (string) ($settings['kashier_server_webhook'] ?? '');
        $config['allowed_methods'] = (string) ($settings['kashier_allowed_methods'] ?? 'card,wallet');

        return $config;
    }

    /**
     * Server-side values only. Admin views should use *_configured flags and
     * must never receive plaintext credential values.
     *
     * @return array<string, mixed>
     */
    public function storedKashierConfig(): array
    {
        $values = $this->all();
        $apiKey = $this->decryptStoredSecret('kashier_api_key_encrypted');
        $secretKey = $this->decryptStoredSecret('kashier_secret_key_encrypted');

        return [
            'mode' => (string) ($values['kashier_mode'] ?? 'live'),
            'base_url' => (string) ($values['kashier_base_url'] ?? 'https://api.kashier.io'),
            'merchant_id' => (string) ($values['kashier_merchant_id'] ?? ''),
            'api_key' => $apiKey,
            'api_key_configured' => $apiKey !== null && $apiKey !== '',
            'secret_key' => $secretKey,
            'secret_key_configured' => $secretKey !== null && $secretKey !== '',
            'server_webhook' => (string) ($values['kashier_server_webhook'] ?? ''),
            'allowed_methods' => (string) ($values['kashier_allowed_methods'] ?? 'card,wallet'),
        ];
    }

    /**
     * Blank secret inputs preserve existing encrypted values.
     *
     * @param array{merchant_id: string, api_key?: ?string, secret_key?: ?string, server_webhook: string} $credentials
     */
    public function saveKashierCredentials(array $credentials): void
    {
        $values = [
            'kashier_mode' => 'live',
            'kashier_base_url' => 'https://api.kashier.io',
            'kashier_merchant_id' => trim($credentials['merchant_id']),
            'kashier_server_webhook' => trim($credentials['server_webhook']),
            'kashier_allowed_methods' => 'card,wallet',
        ];

        foreach (['api_key', 'secret_key'] as $secret) {
            $value = trim((string) ($credentials[$secret] ?? ''));

            if ($value !== '') {
                $values['kashier_'.$secret.'_encrypted'] = Crypt::encryptString($value);
            }
        }

        $this->put($values);
    }

    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            PlatformSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    private function decryptStoredSecret(string $key): ?string
    {
        $encrypted = $this->all()[$key] ?? null;

        if (! is_string($encrypted) || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }
}
