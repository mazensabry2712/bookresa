<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ExpireDueSubscriptions
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {}

    public function handle(?CarbonImmutable $at = null): int
    {
        $at ??= CarbonImmutable::now('UTC');
        $count = 0;

        Subscription::withoutGlobalScopes()
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->where('end_at', '<=', $at)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($at, &$count): void {
                foreach ($subscriptions as $subscription) {
                    $tenant = Tenant::withTrashed()->find((int) $subscription->tenant_id);

                    if ($tenant === null) {
                        continue;
                    }

                    $this->currentTenant->run($tenant, function () use ($subscription, $at, &$count): void {
                        DB::transaction(function () use ($subscription, $at, &$count): void {
                            $locked = Subscription::query()
                                ->whereKey($subscription->getKey())
                                ->lockForUpdate()
                                ->first();

                            if (
                                $locked === null
                                || ! in_array($locked->status, [
                                    SubscriptionStatus::Trial,
                                    SubscriptionStatus::Active,
                                ], true)
                                || $locked->end_at->greaterThan($at)
                            ) {
                                return;
                            }

                            $locked->forceFill([
                                'status' => SubscriptionStatus::Expired,
                                'cancelled_at' => $locked->cancelled_at ?? $locked->end_at,
                            ])->save();

                            $count++;
                        });
                    });
                }
            });

        return $count;
    }
}
