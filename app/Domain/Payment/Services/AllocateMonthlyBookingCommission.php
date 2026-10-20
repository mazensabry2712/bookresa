<?php

namespace App\Domain\Payment\Services;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Models\BookingCommissionAllocation;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Throwable;

final class AllocateMonthlyBookingCommission
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PlatformSettings $settings,
    ) {
    }

    public function handle(Booking $booking): BookingCommissionAllocation
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $booking->tenant_id !== $tenantId) {
            throw new LogicException('Booking commission allocation must use the current workspace.');
        }

        return DB::transaction(function () use ($booking, $tenantId): BookingCommissionAllocation {
            // Lock the booking before checking its allocation so concurrent confirmations
            // of the same booking cannot consume two monthly ordinals.
            $lockedBooking = Booking::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedBooking->status !== BookingStatus::Confirmed) {
                throw new LogicException('Only a confirmed booking can consume a monthly booking slot.');
            }

            $existing = BookingCommissionAllocation::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('booking_id', $lockedBooking->getKey())
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $lockedBooking->loadMissing('service');
            $profile = BusinessProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->first();
            $timezone = trim((string) ($profile?->timezone ?: config('app.timezone', 'UTC')));

            try {
                $nowInWorkspace = CarbonImmutable::now($timezone);
            } catch (Throwable) {
                $timezone = (string) config('app.timezone', 'UTC');
                $nowInWorkspace = CarbonImmutable::now($timezone);
            }

            $periodStart = $nowInWorkspace->startOfMonth()->toDateString();
            $periodEnd = $nowInWorkspace->endOfMonth()->toDateString();

            DB::table('booking_commission_period_counters')->insertOrIgnore([
                'tenant_id' => $tenantId,
                'period_start' => $periodStart,
                'next_ordinal' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('booking_commission_period_counters')
                ->where('tenant_id', $tenantId)
                ->where('period_start', $periodStart)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                throw new RuntimeException('Monthly booking commission counter could not be locked.');
            }

            $ordinal = max(1, (int) $counter->next_ordinal);
            DB::table('booking_commission_period_counters')
                ->where('id', $counter->id)
                ->update([
                    'next_ordinal' => $ordinal + 1,
                    'updated_at' => now(),
                ]);

            $allowance = max(0, (int) $this->settings->get('commission_free_booking_limit', 1000));
            $enabled = filter_var(
                $this->settings->get('booking_commission_enabled', false),
                FILTER_VALIDATE_BOOL,
            );
            // Isolate the monthly policy from the retired customer-profile commission setting.
            $rateBasisPoints = max(
                0,
                min(10000, (int) $this->settings->get('monthly_booking_commission_rate_basis_points', 1000)),
            );
            $bookingAmountMinor = max(
                0,
                (int) ($lockedBooking->service_price_minor_snapshot ?? $lockedBooking->service->price_minor ?? 0),
            );
            $currency = strtoupper(trim((string) (
                $lockedBooking->service_currency_snapshot ?: ($lockedBooking->service->currency ?? '')
            )));

            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new RuntimeException('The booking currency snapshot is invalid for commission allocation.');
            }

            $eligible = $enabled
                && $ordinal > $allowance
                && $rateBasisPoints > 0
                && $bookingAmountMinor > 0;
            $commissionAmountMinor = $eligible
                ? min(intdiv(($bookingAmountMinor * $rateBasisPoints) + 5000, 10000), $bookingAmountMinor)
                : 0;

            $allocation = new BookingCommissionAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'booking_id' => (int) $lockedBooking->getKey(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'timezone' => $timezone,
                'booking_ordinal' => $ordinal,
                'free_booking_allowance' => $allowance,
                'commission_enabled' => $enabled,
                'eligible' => $eligible,
                'rate_basis_points' => $rateBasisPoints,
                'booking_amount_minor_snapshot' => $bookingAmountMinor,
                'commission_amount_minor_snapshot' => $commissionAmountMinor,
                'currency' => $currency,
                'policy_version' => 'monthly-booking-v1',
                'qualified_at' => $nowInWorkspace->utc(),
                'metadata' => [
                    'qualifying_status' => BookingStatus::Confirmed->value,
                    'customer_id' => (int) $lockedBooking->customer_id,
                ],
            ])->save();

            return $allocation->fresh();
        }, 3);
    }
}
