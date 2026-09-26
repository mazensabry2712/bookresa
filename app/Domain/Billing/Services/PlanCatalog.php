<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PlanCatalog
{
    private const ACTIVE_PLANS_CACHE_KEY = 'bookresa:billing:active-plans';

    /** @return Collection<int, Plan> */
    public function active(): Collection
    {
        return Cache::remember(
            self::ACTIVE_PLANS_CACHE_KEY,
            now()->addMinutes(5),
            fn (): Collection => Plan::query()
                ->active()
                ->orderBy('price_minor')
                ->get([
                    'id',
                    'name',
                    'price_minor',
                    'currency',
                ]),
        );
    }

    public function forget(): void
    {
        Cache::forget(self::ACTIVE_PLANS_CACHE_KEY);
    }
}
