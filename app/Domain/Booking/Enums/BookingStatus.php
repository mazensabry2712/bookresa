<?php

namespace App\Domain\Booking\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
    case NoShow = 'no_show';

    public function blocksAvailability(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed, self::Rescheduled => true,
            default => false,
        };
    }
}
