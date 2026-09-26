<?php

namespace App\Http\Middleware;

use App\Domain\Module\Services\TenantModuleAccess;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantModuleEnabled
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantModuleAccess $access,
    ) {}

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

        $result = $this->currentTenant->run(
            $tenant,
            fn (): bool => $this->access->allows($moduleKey),
        );

        abort_unless(
            $result,
            Response::HTTP_FORBIDDEN,
            'This feature is not enabled for the current workspace or subscription plan.',
        );

        return $next($request);
    }
}
