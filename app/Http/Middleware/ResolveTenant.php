<?php

namespace App\Http\Middleware;

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
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

        $requestedTenantId = $request->session()->get('tenant_id');

        $membershipQuery = $user->tenantMemberships()
            ->where('status', MembershipStatus::Active->value)
            ->with('tenant');

        if ($requestedTenantId !== null) {
            $membershipQuery->where('tenant_id', (int) $requestedTenantId);
        }

        $membership = $membershipQuery
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($membership === null && $requestedTenantId !== null) {
            $membership = $user->tenantMemberships()
                ->where('status', MembershipStatus::Active->value)
                ->with('tenant')
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->first();
        }

        $tenant = $membership?->tenant;

        abort_unless(
            $tenant !== null && $tenant->status === TenantStatus::Active,
            Response::HTTP_FORBIDDEN,
            'No active workspace is available for this account.',
        );

        $request->session()->put('tenant_id', $tenant->getKey());
        $this->currentTenant->set($tenant);
        setPermissionsTeamId($tenant->getKey());

        $user->unsetRelation('roles')->unsetRelation('permissions');

        try {
            return $next($request);
        } finally {
            $this->currentTenant->clear();
            setPermissionsTeamId(null);
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
