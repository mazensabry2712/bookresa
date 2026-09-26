<?php

namespace App\Console\Commands;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\SubscriptionExpiryNotification;
use App\Notifications\UsageWarningNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SendBillingNotifications extends Command
{
    protected $signature = 'bookresa:send-billing-notifications {--expiry-hours=24 : Hours before subscription expiry to warn} {--usage-threshold=80 : Customer usage percentage that triggers a warning}';

    protected $description = 'Queue subscription expiry and customer usage warning notifications.';

    public function handle(
        CurrentTenant $currentTenant,
        CalculateSubscriptionUsage $usageCalculator,
    ): int {
        $now = CarbonImmutable::now('UTC');
        $expiryHours = max((int) $this->option('expiry-hours'), 1);
        $threshold = min(max((int) $this->option('usage-threshold'), 1), 100);
        $expiryQueued = 0;
        $usageQueued = 0;

        $subscriptions = Subscription::withoutGlobalScopes()
            ->with(['tenant.profile', 'plan'])
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->get();

        foreach ($subscriptions as $subscription) {
            $tenant = $subscription->tenant;

            if (! $tenant instanceof Tenant) {
                continue;
            }

            $currentTenant->set($tenant);

            $owner = $tenant->memberships()
                ->where('status', MembershipStatus::Active->value)
                ->where('is_primary', true)
                ->with('user')
                ->first()?->user;

            if ($owner === null) {
                continue;
            }

            if (
                $subscription->end_at !== null
                && $subscription->end_at->between(
                    $now,
                    $now->addHours($expiryHours),
                    true,
                )
                && ! $owner->notifications()
                    ->where('type', SubscriptionExpiryNotification::class)
                    ->where('created_at', '>=', $now->subDay())
                    ->exists()
            ) {
                $owner->notify(new SubscriptionExpiryNotification($subscription));
                $expiryQueued++;
            }

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
                $usageQueued++;
            }
        }

        $currentTenant->clear();

        $this->info("Queued {$expiryQueued} expiry and {$usageQueued} usage notification(s).");

        return self::SUCCESS;
    }
}
