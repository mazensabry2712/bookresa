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
            if (! $isCore) {
                return false;
            }

            if (! (bool) data_get($this->currentTenant->get()?->settings, 'onboarding.completed', false)) {
                return true;
            }

            return $this->usableSubscriptionExists($tenantId);
        }

        if (! $module->is_active) {
            return false;
        }

        $tenantModule = TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('module_id', $module->getKey())
            ->first();

        if ($module->is_core || $isCore) {
            if ($tenantModule?->enabled === false) {
                return false;
            }

            if (! (bool) data_get($this->currentTenant->get()?->settings, 'onboarding.completed', false)) {
                return true;
            }

            return $this->usableSubscriptionExists($tenantId);
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

    /**
     * Determine whether the module is otherwise enabled but blocked only because
     * a completed workspace has no usable subscription.
     */
    public function requiresSubscription(string $moduleKey): bool
    {
        $tenant = $this->currentTenant->get();

        if (
            $tenant === null
            || ! (bool) data_get($tenant->settings, 'onboarding.completed', false)
        ) {
            return false;
        }

        $module = Module::query()->where('key', $moduleKey)->first();
        $isCore = in_array($moduleKey, config('bookresa.modules.core', []), true);

        if ($module === null) {
            return $isCore;
        }

        if (! $module->is_active) {
            return false;
        }

        $tenantModule = TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->where('module_id', $module->getKey())
            ->first();

        if ($module->is_core || $isCore) {
            return $tenantModule?->enabled !== false;
        }

        return $tenantModule?->enabled === true;
    }

    private function usableSubscriptionExists(int $tenantId): bool
    {
        return Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->get()
            ->contains(fn (Subscription $subscription): bool => $subscription->isUsable());
    }

}
