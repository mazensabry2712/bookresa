<?php

namespace App\Http\Middleware;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Services\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantSubscriptionUsable
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->currentTenant->get();

        abort_unless($tenant !== null, Response::HTTP_FORBIDDEN, 'An active workspace is required.');

        if ((bool) data_get($tenant->settings, 'onboarding.completed', false)) {
            $subscription = Subscription::query()
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                ])
                ->latest('start_at')
                ->get()
                ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

            abort_unless(
                $subscription !== null,
                Response::HTTP_FORBIDDEN,
                'An active subscription is required to use this workspace.',
            );
        }

        return $next($request);
    }
}
