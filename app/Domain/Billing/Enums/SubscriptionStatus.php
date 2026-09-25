<?php

namespace App\Domain\Billing\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function isUsable(): bool
    {
        return in_array($this, [self::Trial, self::Active], true);
    }
}
