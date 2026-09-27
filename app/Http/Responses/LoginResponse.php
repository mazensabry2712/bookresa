<?php

namespace App\Http\Responses;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

final class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return to_route('login');
        }

        if (! $user->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }

        if (PlatformAdmin::query()
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->exists()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        $hasActiveTenant = $user->tenantMemberships()
            ->where('status', MembershipStatus::Active->value)
            ->whereHas(
                'tenant',
                fn ($query) => $query->where('status', TenantStatus::Active->value)
            )
            ->exists();

        if ($hasActiveTenant) {
            return redirect()->intended(route('dashboard'));
        }

        return to_route('onboarding.business.create');
    }
}
