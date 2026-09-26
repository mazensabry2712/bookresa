<?php

namespace App\Support;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

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
}
