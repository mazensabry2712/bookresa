<?php

namespace App\Http\Middleware;

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        setPermissionsTeamId(null);
        $this->currentTenant->clear();

        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $routeTenant = $request->route('tenant');
        $hasExplicitTenant = $routeTenant instanceof Tenant
            || (is_string($routeTenant) && $routeTenant !== '');

        $tenant = null;

        if ($routeTenant instanceof Tenant) {
            $tenant = $routeTenant;
        } elseif (is_string($routeTenant) && $routeTenant !== '') {
            $tenant = Tenant::query()->where('slug', $routeTenant)->first();

            abort_unless(
                $tenant !== null,
                Response::HTTP_NOT_FOUND,
                'Workspace not found.',
            );
        }

        if ($hasExplicitTenant) {
            $membershipExists = $user->tenantMemberships()
                ->where('tenant_id', $tenant->getKey())
                ->where('status', MembershipStatus::Active->value)
                ->whereHas(
                    'tenant',
                    fn ($query) => $query->where('status', TenantStatus::Active->value)
                )
                ->exists();

            abort_unless(
                $membershipExists && $tenant->status === TenantStatus::Active,
                Response::HTTP_FORBIDDEN,
                'You do not have access to this workspace.',
            );
        } else {
            $requestedTenantId = $request->session()->get('tenant_id');

            $membershipQuery = $user->tenantMemberships()
                ->where('status', MembershipStatus::Active->value)
                ->whereHas(
                    'tenant',
                    fn ($query) => $query->where('status', TenantStatus::Active->value)
                )
                ->with('tenant');

            $membership = null;

            if ($requestedTenantId !== null) {
                $membership = (clone $membershipQuery)
                    ->where('tenant_id', (int) $requestedTenantId)
                    ->first();
            }

            if ($membership === null) {
                $membership = $membershipQuery
                    ->orderByDesc('is_primary')
                    ->orderBy('id')
                    ->first();
            }

            $tenant = $membership?->tenant;
        }

        abort_unless(
            $tenant !== null && $tenant->status === TenantStatus::Active,
            Response::HTTP_FORBIDDEN,
            'No active workspace is available for this account.',
        );

        $request->session()->put('tenant_id', $tenant->getKey());

        // Keep the resolved tenant available for Laravel's controller/model binding.
        $request->route()?->setParameter('tenant', $tenant);

        $this->currentTenant->set($tenant);
        setPermissionsTeamId($tenant->getKey());

        // Once a tenant is resolved, all named workspace routes generated during
        // this request automatically receive the current tenant slug.
        URL::defaults(['tenant' => $tenant->slug]);

        $user->unsetRelation('roles')->unsetRelation('permissions');

        try {
            return $next($request);
        } finally {
            $this->currentTenant->clear();
            setPermissionsTeamId(null);
            URL::defaults([]);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
