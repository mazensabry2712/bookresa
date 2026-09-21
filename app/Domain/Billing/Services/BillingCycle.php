<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Enums\PlanBillingPeriod;
use Carbon\CarbonImmutable;

final class BillingCycle
{
    public function endAt(CarbonImmutable $startAt, PlanBillingPeriod $period): CarbonImmutable
    {
        return $startAt->addMonths($period->months());
    }
}
