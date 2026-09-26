<?php

namespace App\Http\Middleware;

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

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $tenant = $this->currentTenant->get();

        if ($tenant === null) {
            $routeTenant = $request->route('tenant');

            if ($routeTenant instanceof Tenant) {
                $tenant = $routeTenant;
            }
        }

        abort_unless(
            $tenant !== null
                && TenantModule::withoutGlobalScope('tenant')
                    ->where('tenant_id', $tenant->getKey())
                    ->where('enabled', true)
                    ->whereHas('module', fn ($query) => $query
                        ->where('key', $module)
                        ->where('is_active', true))
                    ->exists(),
            Response::HTTP_FORBIDDEN,
            'This feature is not enabled for the current workspace.',
        );

        return $next($request);
    }
}
