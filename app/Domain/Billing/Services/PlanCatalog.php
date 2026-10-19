<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PlanCatalog
{
    // Retained only so deployments can explicitly remove the legacy cached
    // Collection. Active plans are queried directly to avoid unserializing
    // stale/incompatible PHP objects from the shared production cache.
    private const LEGACY_ACTIVE_PLANS_CACHE_KEY = 'bookresa:billing:active-plans:v2';

    /** @return Collection<int, Plan> */
    public function active(): Collection
    {
        return Plan::query()
            ->active()
            ->orderBy('price_minor')
            ->get([
                'id',
                'name',
                'price_minor',
                'currency',
                'billing_period',
                'trial_days',
            ]);
    }

    public function forget(): void
    {
        Cache::forget(self::LEGACY_ACTIVE_PLANS_CACHE_KEY);
    }
}
