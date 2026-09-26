<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Module\Models\Module;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class UpsertPlan
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, int> $moduleIds
     */
    public function handle(?Plan $plan, array $data, array $moduleIds = []): Plan
    {
        $price = (int) ($data['price_minor'] ?? 0);
        $included = (int) ($data['included_customer_limit'] ?? 0);
        $extra = (int) ($data['additional_customer_price_minor'] ?? 0);
        $currency = strtoupper(trim((string) ($data['currency'] ?? '')));

        if ($price < 0 || $included < 0 || $extra < 0) {
            throw new RuntimeException('Plan monetary values and customer limits cannot be negative.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('Plan currency must be a 3-letter ISO code.');
        }

        $period = PlanBillingPeriod::tryFrom((string) ($data['billing_period'] ?? ''));

        if ($period === null) {
            throw new RuntimeException('Plan billing period is invalid.');
        }

        if (! is_array($data['name'] ?? null) || blank($data['name']['en'] ?? null)) {
            throw new RuntimeException('Plan English name is required.');
        }

        $plan = DB::transaction(function () use ($plan, $data, $moduleIds, $price, $included, $extra, $currency, $period): Plan {
            $plan ??= new Plan();

            $plan->forceFill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price_minor' => $price,
                'currency' => $currency,
                'billing_period' => $period,
                'included_customer_limit' => $included,
                'additional_customer_price_minor' => $extra,
                'trial_days' => max((int) ($data['trial_days'] ?? 0), 0),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'metadata' => $data['metadata'] ?? null,
            ])->save();

            $validModuleIds = Module::query()
                ->whereIn('id', $moduleIds)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $plan->modules()->sync(array_fill_keys($validModuleIds, []));

            return $plan->fresh('modules');
        });

        app(PlanCatalog::class)->forget();

        return $plan;
    }
}
