<?php

namespace App\Domain\Service\Actions;

use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class UpdateService
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Service $service, array $data): Service
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $service->tenant_id !== $tenantId) {
            throw new LogicException('Service must belong to the current tenant.');
        }

        return DB::transaction(function () use ($service, $data): Service {
            $service->fill([
                'name' => [
                    'en' => $data['name_en'],
                    'ar' => $data['name_ar'] ?: $data['name_en'],
                ],
                'description' => [
                    'en' => $data['description_en'] ?: null,
                    'ar' => $data['description_ar'] ?: null,
                ],
                'price_minor' => $this->toMinorUnits($data['price']),
                'currency' => strtoupper($data['currency']),
                'duration_minutes' => (int) $data['duration_minutes'],
                'buffer_minutes' => (int) $data['buffer_minutes'],
                'is_active' => (bool) ($data['is_active'] ?? false),
            ])->save();

            return $service->fresh();
        });
    }

    private function toMinorUnits(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException('Invalid service price.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
