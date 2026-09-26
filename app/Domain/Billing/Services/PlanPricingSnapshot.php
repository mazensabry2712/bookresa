<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Plan;
use App\Domain\Module\Models\Module;
use Illuminate\Database\Eloquent\Collection;

final class PlanPricingSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function make(Plan $plan): array
    {
        $plan->loadMissing('modules');

        /** @var Collection<int, Module> $modules */
        $modules = $plan->modules;

        return [
            'plan_id' => $plan->getKey(),
            'name' => $plan->name,
            'description' => $plan->description,
            'price_minor' => (int) $plan->price_minor,
            'currency' => $plan->currency,
            'billing_period' => $plan->billing_period->value,
            'included_customer_limit' => (int) $plan->included_customer_limit,
            'additional_customer_price_minor' => (int) $plan->additional_customer_price_minor,
            'trial_days' => (int) $plan->trial_days,
            'modules' => $modules->map(function (Module $module): array {
                return [
                    'key' => $module->key,
                    'settings' => data_get($module->getRelationValue('pivot'), 'settings', []),
                ];
            })->values()->all(),
        ];
    }
}
