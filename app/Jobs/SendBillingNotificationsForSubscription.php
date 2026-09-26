<?php

namespace App\Jobs;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\SubscriptionExpiryNotification;
use App\Notifications\UsageWarningNotification;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendBillingNotificationsForSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $subscriptionId,
        private readonly int $expiryHours = 24,
        private readonly int $usageThreshold = 75,
    ) {
    }

    public function handle(
        CurrentTenant $currentTenant,
        CalculateSubscriptionUsage $usageCalculator,
    ): void {
        $subscription = Subscription::withoutGlobalScopes()
            ->with([
                'tenant.profile',
                'tenant.memberships' => fn ($query) => $query
                    ->where('status', MembershipStatus::Active->value)
                    ->where('is_primary', true)
                    ->with('user'),
                'plan',
            ])
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->find($this->subscriptionId);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $tenant = $subscription->tenant;

        if (! $tenant instanceof Tenant) {
            return;
        }

        $currentTenant->set($tenant);

        try {
            $owner = $tenant->memberships->first()?->user;

            if ($owner === null) {
                return;
            }

            $now = CarbonImmutable::now('UTC');

            if (
                $subscription->end_at !== null
                && $subscription->end_at->between(
                    $now,
                    $now->addHours(max($this->expiryHours, 1)),
                    true,
                )
                && ! $owner->notifications()
                    ->where('type', SubscriptionExpiryNotification::class)
                    ->where('created_at', '>=', $now->subDay())
                    ->exists()
            ) {
                $owner->notify(new SubscriptionExpiryNotification($subscription));
            }

            $threshold = min(max($this->usageThreshold, 1), 100);
            $summary = $usageCalculator->handle($subscription);
            $limit = $summary->includedCustomerLimit;
            $customerCount = $summary->uniqueCustomerCount;
            $thresholdReached = $limit > 0
                ? $customerCount >= (int) ceil($limit * ($threshold / 100))
                : $customerCount > 0;

            if (
                $thresholdReached
                && ! $owner->notifications()
                    ->where('type', UsageWarningNotification::class)
                    ->where('created_at', '>=', $now->subDay())
                    ->exists()
            ) {
                $owner->notify(new UsageWarningNotification(
                    $subscription,
                    $customerCount,
                    $limit,
                    $threshold,
                ));
            }
        } finally {
            $currentTenant->clear();
        }
    }
}
