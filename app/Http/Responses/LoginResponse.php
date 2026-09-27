<?php

namespace App\Http\Responses;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
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
                return $this->onboardingRedirect($tenant);
            }

            $subscription = Subscription::withoutGlobalScopes()
                ->where('tenant_id', $tenant->getKey())
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                ])
                ->latest('start_at')
                ->get()
                ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

            return redirect()->intended(
                $subscription !== null ? route('dashboard') : route('billing.subscription')
            );
        }

        return to_route('onboarding.business.create');
    }
    private function onboardingRedirect(\App\Domain\Tenant\Models\Tenant $tenant): RedirectResponse
    {
        return match ((string) data_get($tenant->settings, 'onboarding.step', 'services')) {
            'hours' => to_route('scheduling.index'),
            'staff' => to_route('staff.index'),
            default => to_route('services.index'),
        };
    }

}