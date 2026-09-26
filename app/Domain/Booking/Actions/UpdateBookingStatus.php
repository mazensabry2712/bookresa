<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Notifications\BookingNotification;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
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
        private readonly AuditLogger $audit,
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

            $notificationKind = match ($status) {
                BookingStatus::Confirmed => 'confirmed',
                BookingStatus::Cancelled => 'cancelled',
                BookingStatus::Rescheduled => 'rescheduled',
                default => null,
            };

            $fresh = $booking->fresh(['customer', 'service', 'staff', 'statusHistory']);

            if ($notificationKind !== null) {
                $fresh?->customer?->notify(new BookingNotification($fresh, $notificationKind));
            }

            $this->audit->log(
                'booking.status_changed',
                $fresh,
                [
                    'tenant_id' => (int) $fresh->tenant_id,
                    'booking_id' => (int) $fresh->getKey(),
                    'from_status' => $from->value,
                    'to_status' => $status->value,
                ],
            );

            return $fresh;
        });
    }
}
