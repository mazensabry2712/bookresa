<?php

namespace App\Http\Responses;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
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

            return $subscription !== null
                ? to_route('dashboard', ['verified' => 1])
                : to_route('billing.subscription', ['verified' => 1]);
        }

        return to_route('onboarding.business.create', ['verified' => 1]);
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