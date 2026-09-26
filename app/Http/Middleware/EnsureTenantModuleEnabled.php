<?php

namespace App\Http\Middleware;

use App\Domain\Module\Models\Module;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantModuleEnabled
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $tenant = $this->currentTenant->get();

        if ($tenant === null) {
            $routeTenant = $request->route('tenant');

            if ($routeTenant instanceof Tenant) {
                $tenant = $routeTenant;
            }
        }

        abort_unless($tenant !== null, Response::HTTP_FORBIDDEN, 'A workspace is required for this feature.');

        $module = Module::query()
            ->where('key', $moduleKey)
            ->first();

        $isCore = in_array($moduleKey, config('bookresa.modules.core', []), true);

        if ($module === null) {
            abort_unless(
                $isCore,
                Response::HTTP_FORBIDDEN,
                'This feature is not available.',
            );

            return $next($request);
        }

        abort_unless(
            $module->is_active,
            Response::HTTP_FORBIDDEN,
            'This feature is not available.',
        );

        $tenantModule = TenantModule::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->getKey())
            ->where('module_id', $module->getKey())
            ->first();

        if ($module->is_core || $isCore) {
            abort_if(
                $tenantModule !== null && ! $tenantModule->enabled,
                Response::HTTP_FORBIDDEN,
                'This feature is not enabled for the current workspace.',
            );

            return $next($request);
        }

        abort_unless(
            $tenantModule?->enabled === true,
            Response::HTTP_FORBIDDEN,
            'This feature is not enabled for the current workspace.',
        );

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        $entitled = collect(data_get($subscription?->pricing_snapshot, 'modules', []))
            ->contains(fn (array $item): bool => ($item['key'] ?? null) === $moduleKey);

        abort_unless(
            $entitled,
            Response::HTTP_FORBIDDEN,
            'This feature is not included in the current subscription plan.',
        );

        return $next($request);
    }
}
