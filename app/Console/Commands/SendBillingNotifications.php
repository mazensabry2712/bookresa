<?php

namespace App\Console\Commands;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use Illuminate\Console\Command;

final class SendBillingNotifications extends Command
{
    protected $signature = 'bookresa:send-billing-notifications {--expiry-hours=24 : Hours before subscription expiry to warn} {--usage-threshold=80 : Customer usage percentage that triggers a warning}';

    protected $description = 'Queue subscription expiry and customer usage warning notifications.';

    public function handle(): int {
        $expiryHours = max((int) $this->option('expiry-hours'), 1);
        $threshold = min(max((int) $this->option('usage-threshold'), 1), 100);
        $dispatched = 0;

        Subscription::withoutGlobalScopes()
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($expiryHours, $threshold, &$dispatched): void {
                foreach ($subscriptions as $subscription) {
                    SendBillingNotificationsForSubscription::dispatch(
                        (int) $subscription->getKey(),
                        $expiryHours,
                        $threshold,
                    );

                    $dispatched++;
                }
            });


        $this->info("Dispatched {$dispatched} subscription billing notification job(s).");

        return self::SUCCESS;
    }
}
