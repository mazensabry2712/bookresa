<?php

namespace App\Http\Responses;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

final class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        if ($user === null) {
            return to_route('login');
        }

        if (PlatformAdmin::query()
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->exists()) {
            return redirect()->intended(route('admin.dashboard').'?verified=1');
        }

        $membership = $user->tenantMemberships()
            ->where('status', MembershipStatus::Active->value)
            ->whereHas(
                'tenant',
                fn ($query) => $query->where('status', TenantStatus::Active->value)
            )
            ->with('tenant')
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        if ($membership?->tenant !== null) {
            $tenant = $membership->tenant;

            if (! (bool) data_get($tenant->settings, 'onboarding.completed', false)) {
                return redirect()->intended(route('onboarding.workspace').'?verified=1');
            }

            return redirect()->intended(route('dashboard').'?verified=1');
        }

        return redirect()->intended(route('onboarding.business.create').'?verified=1');
    }
}
