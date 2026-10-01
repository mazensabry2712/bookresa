<?php

namespace App\Domain\Payment\Enums;

enum TenantPaymentAccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Disabled = 'disabled';
}
