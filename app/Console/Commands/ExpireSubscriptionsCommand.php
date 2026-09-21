<?php

namespace App\Console\Commands;

use App\Domain\Billing\Services\ExpireDueSubscriptions;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions whose billing period has ended.';

    public function handle(ExpireDueSubscriptions $expirer): int
    {
        $count = $expirer->handle();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
