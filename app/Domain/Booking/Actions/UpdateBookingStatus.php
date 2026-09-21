<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

final class UpdateBookingStatus
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled', 'rescheduled', 'no_show'],
        'rescheduled' => ['confirmed', 'cancelled'],
    ];

    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(
        Booking $booking,
        BookingStatus $status,
        ?string $reason = null,
    ): Booking {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking must belong to the current tenant.');
        }

        return DB::transaction(function () use ($booking, $status, $reason): Booking {
            $booking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $from = $booking->status;

            if ($from === $status) {
                return $booking;
            }

            $allowed = self::TRANSITIONS[$from->value] ?? [];

            if (! in_array($status->value, $allowed, true)) {
                throw new RuntimeException("Invalid booking status transition from {$from->value} to {$status->value}.");
            }

            $booking->forceFill(['status' => $status])->save();

            $booking->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $status->value,
                'changed_by' => auth()->id(),
                'reason' => $reason,
            ]);

            return $booking->fresh(['customer', 'service', 'staff', 'statusHistory']);
        });
    }
}
