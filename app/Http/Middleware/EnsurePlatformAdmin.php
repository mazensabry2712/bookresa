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

        abort_unless(
            $request->user() !== null
                && PlatformAdmin::query()
                    ->where('user_id', $request->user()->getKey())
                    ->where('is_active', true)
                    ->exists(),
            Response::HTTP_FORBIDDEN,
            'Platform administrator access is required.',
        );

        try {
            return $next($request);
        } finally {
            $this->currentTenant->clear();
            setPermissionsTeamId(null);
        }
    }
}
