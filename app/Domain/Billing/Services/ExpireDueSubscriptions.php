<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ExpireDueSubscriptions
{
    public function handle(?CarbonImmutable $at = null): int
    {
        $at ??= CarbonImmutable::now('UTC');

        return DB::transaction(function () use ($at): int {
            $count = 0;

            Subscription::query()
                ->whereIn('status', [
                    SubscriptionStatus::Trial->value,
                    SubscriptionStatus::Active->value,
                ])
                ->where('end_at', '<=', $at)
                ->lockForUpdate()
                ->each(function (Subscription $subscription) use (&$count): void {
                    $subscription->forceFill([
                        'status' => SubscriptionStatus::Expired,
                        'cancelled_at' => $subscription->cancelled_at ?? $subscription->end_at,
                    ])->save();

                    $count++;
                });

            return $count;
        });
    }
}
