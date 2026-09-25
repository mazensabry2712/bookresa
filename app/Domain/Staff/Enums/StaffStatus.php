<?php

namespace App\Domain\Staff\Enums;

enum StaffStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
