<?php

namespace App\Console\Commands;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CreateUsagePeriod;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class GenerateUsagePeriodsCommand extends Command
{
    protected $signature = 'bookresa:generate-usage-periods';

    protected $description = 'Create idempotent usage snapshots for expired subscription periods.';

    public function handle(CurrentTenant $currentTenant, CreateUsagePeriod $createUsagePeriod): int
    {
        $created = 0;
        $skipped = 0;

        Subscription::withoutGlobalScopes()
            ->where('status', SubscriptionStatus::Expired->value)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('usage_periods')
                    ->whereColumn('usage_periods.subscription_id', 'subscriptions.id')
                    ->whereColumn('usage_periods.tenant_id', 'subscriptions.tenant_id')
                    ->whereColumn('usage_periods.period_start', 'subscriptions.start_at')
                    ->whereColumn('usage_periods.period_end', 'subscriptions.end_at');
            })
            ->orderBy('subscriptions.id')
            ->chunkById(100, function (Collection $subscriptions) use ($currentTenant, $createUsagePeriod, &$created, &$skipped): void {
                /** @var Collection<int, Subscription> $subscriptions */
                foreach ($subscriptions as $subscription) {
                    $tenant = Tenant::withTrashed()->find((int) $subscription->tenant_id);

                    if (! $tenant instanceof Tenant || $tenant->trashed()) {
                        $skipped++;

                        continue;
                    }

                    $currentTenant->set($tenant);

                    try {
                        $period = $createUsagePeriod->handle($subscription);

                        if ($period->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $skipped++;
                        }
                    } finally {
                        $currentTenant->clear();
                    }
                }
            });

        $this->info("Created {$created} usage period(s); skipped {$skipped} existing or unavailable period(s).");

        return self::SUCCESS;
    }
}
