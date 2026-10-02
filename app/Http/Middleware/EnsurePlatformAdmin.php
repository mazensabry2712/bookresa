<?php

namespace App\Http\Middleware;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformAdmin
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
        $platformAdmin = $user?->relationLoaded('platformAdmin')
            ? $user->platformAdmin
            : $user?->load('platformAdmin')->platformAdmin;

        abort_unless(
            $user !== null
                && $platformAdmin instanceof PlatformAdmin
                && $platformAdmin->is_active,
            Response::HTTP_FORBIDDEN,
            'Platform administrator access is required.',
        );

        $permission = $this->routePermission($request);

        abort_unless(
            $permission === null || $platformAdmin->hasPlatformPermission($permission),
            Response::HTTP_FORBIDDEN,
            'The current platform administrator role does not allow this operation.',
        );

        try {
            return $next($request);
        } finally {
            $this->currentTenant->clear();
            setPermissionsTeamId(null);
        }
    }

    private function routePermission(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        return config('platform.route_permissions.'.$routeName);
    }
}
