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
            return to_route('admin.dashboard', ['verified' => 1]);
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

            return to_route('dashboard', ['tenant' => $tenant->slug, 'verified' => 1]);
        }

        return to_route('onboarding.business.create', ['verified' => 1]);
    }
}