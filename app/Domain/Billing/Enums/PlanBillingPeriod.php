<?php

namespace App\Domain\Billing\Enums;

enum PlanBillingPeriod: string
{
    case Monthly = 'monthly';
    case ThreeMonths = '3_months';
    case SixMonths = '6_months';
    case Yearly = 'yearly';

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::ThreeMonths => 3,
            self::SixMonths => 6,
            self::Yearly => 12,
        };
    }
}
