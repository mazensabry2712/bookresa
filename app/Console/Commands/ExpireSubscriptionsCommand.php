<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\ExpireDueSubscriptions;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions whose billing period has ended.';

    public function handle(CurrentTenant $currentTenant, ExpireDueSubscriptions $expirer): int
    {
        $count = 0;

        Tenant::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $tenants) use ($currentTenant, $expirer, &$count): void {
                foreach ($tenants as $tenant) {
                    $count += $currentTenant->run(
                        $tenant,
                        fn (): int => $expirer->handle(),
                    );
                }
            });

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }

}
