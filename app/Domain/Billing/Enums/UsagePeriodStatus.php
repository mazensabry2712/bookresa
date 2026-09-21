<?php

namespace App\Domain\Billing\Enums;

enum UsagePeriodStatus: string
{
    case Open = 'open';
    case Invoiced = 'invoiced';
    case Paid = 'paid';
    case Void = 'void';
}
