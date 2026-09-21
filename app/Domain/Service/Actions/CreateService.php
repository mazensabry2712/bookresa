<?php

namespace App\Domain\Service\Actions;

use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Services\CurrentTenant;
use RuntimeException;

final class CreateService
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(array $data): Service
    {
        $tenantId = $this->currentTenant->idOrFail();

        $duration = (int) ($data['duration_minutes'] ?? 0);
        $buffer = (int) ($data['buffer_minutes'] ?? 0);
        $price = (int) ($data['price_minor'] ?? 0);

        if ($duration < 1) {
            throw new RuntimeException('Service duration must be greater than zero.');
        }

        if ($buffer < 0 || $price < 0) {
            throw new RuntimeException('Service price and buffer cannot be negative.');
        }

        return Service::query()->create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_minor' => $price,
            'currency' => strtoupper($data['currency'] ?? 'EGP'),
            'duration_minutes' => $duration,
            'buffer_minutes' => $buffer,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
