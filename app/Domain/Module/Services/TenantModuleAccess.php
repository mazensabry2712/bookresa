<?php

namespace App\Domain\Module\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Tenant\Services\CurrentTenant;

final class TenantModuleAccess
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function allows(string $moduleKey): bool
    {
        $tenantId = $this->currentTenant->idOrFail();
        $module = Module::query()
            ->where('key', $moduleKey)
            ->first();

        $isCore = in_array($moduleKey, config('bookresa.modules.core', []), true);

        if ($module === null) {
            return $isCore;
        }

        if (! $module->is_active) {
            return false;
        }

        $tenantModule = TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('module_id', $module->getKey())
            ->first();

        if ($module->is_core || $isCore) {
            return $tenantModule?->enabled !== false;
        }

        if ($tenantModule?->enabled !== true) {
            return false;
        }

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->get()
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        if ($subscription === null) {
            return false;
        }

        return collect(data_get($subscription->pricing_snapshot, 'modules', []))
            ->contains(fn (array $item): bool => ($item['key'] ?? null) === $moduleKey);
    }
}
