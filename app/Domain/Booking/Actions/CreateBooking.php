<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Notifications\BookingNotification;
use App\Notifications\BusinessBookingNotification;
use App\Domain\Customer\Actions\FindOrCreateCustomer;
use App\Domain\Customer\Models\Customer;
use App\Domain\Service\Models\Service;
use App\Domain\Scheduling\Services\AvailabilityService;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use RuntimeException;

final class CreateBooking
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly AvailabilityService $availability,
        private readonly FindOrCreateCustomer $customers,
    ) {
    }

    public function handle(
        Service $service,
        string $customerName,
        ?string $phone,
        ?string $email,
        CarbonImmutable $startsAt,
        ?StaffProfile $requestedStaff = null,
        ?string $notes = null,
        ?Customer $existingCustomer = null,
        ?string $paymentMethod = null,
    ): Booking {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $service->tenant_id !== $tenantId) {
            throw new LogicException('Service must belong to the current tenant.');
        }

        if ($existingCustomer !== null && (int) $existingCustomer->tenant_id !== $tenantId) {
            throw new LogicException('Customer must belong to the current tenant.');
        }

        $timezone = $this->timezone();
        $localStart = $startsAt->setTimezone($timezone);

        if ($localStart->lessThanOrEqualTo(CarbonImmutable::now($timezone))) {
            throw new RuntimeException('Bookings must be scheduled in the future.');
        }

        $hasStaffAssignments = $service->staff()->exists();

        /** @var Collection<int, StaffProfile> $assignedStaff */
        $assignedStaff = $service->staff()
            ->where('status', StaffStatus::Active->value)
            ->orderBy('staff_profiles.id')
            ->get();

        if ($requestedStaff !== null) {
            if ((int) $requestedStaff->tenant_id !== $tenantId) {
                throw new LogicException('Staff must belong to the current tenant.');
            }

            if ($requestedStaff->status !== StaffStatus::Active) {
                throw new RuntimeException(__('app.booking_ui.no_staff_available_at_time'));
            }

            if (! $hasStaffAssignments) {
                throw new LogicException('This service does not use staff assignments.');
            }

            if (! $assignedStaff->contains(fn (StaffProfile $staff): bool => $staff->is($requestedStaff))) {
                throw new LogicException('Selected staff member is not assigned to this service.');
            }
        }

        // A service configured for staff must never fall back to an unassigned booking
        // just because all of its assigned staff are currently inactive.
        if ($requestedStaff === null && $assignedStaff->isEmpty() && $hasStaffAssignments) {
            throw new RuntimeException(__('app.booking_ui.no_active_staff_assigned'));
        }

        $booking = DB::transaction(function () use (
            $service,
            $customerName,
            $phone,
            $email,
            $localStart,
            $requestedStaff,
            $assignedStaff,
            $notes,
            $tenantId,
            $existingCustomer,
            $paymentMethod,
        ): Booking {
            if ($assignedStaff->isEmpty()) {
                $this->lockScope($tenantId, null, $localStart);
                $selectedStaff = null;

                if (! $this->availability->isAvailable($service, $localStart, null)) {
                    throw new RuntimeException(__('app.booking_ui.selected_time_unavailable'));
                }

                return $this->persistBooking(
                    $service,
                    $customerName,
                    $phone,
                    $email,
                    $localStart,
                    $selectedStaff,
                    $notes,
                    $existingCustomer,
                    $paymentMethod,
                );
            }

            /** @var Collection<int, StaffProfile> $candidates */
            $candidates = $requestedStaff !== null
                ? collect([$requestedStaff])
                : $assignedStaff->sortBy(fn (StaffProfile $staff): int => $staff->getKey());

            foreach ($candidates as $staff) {
                $this->lockScope($tenantId, $staff->getKey(), $localStart);

                if ($this->availability->isAvailable($service, $localStart, $staff)) {
                    return $this->persistBooking(
                        $service,
                        $customerName,
                        $phone,
                        $email,
                        $localStart,
                        $staff,
                        $notes,
                        $existingCustomer,
                        $paymentMethod,
                    );
                }
            }

            throw new RuntimeException(__('app.booking_ui.no_staff_available_at_time'));
        }, 3);

        $booking->customer?->notify(new BookingNotification($booking, 'created'));

        $owner = $this->currentTenant->get()?->memberships()
            ->where('is_primary', true)
            ->with('user')
            ->first()?->user;

        $owner?->notify(new BusinessBookingNotification($booking, 'created'));

        return $booking;
    }

    private function persistBooking(
        Service $service,
        string $customerName,
        ?string $phone,
        ?string $email,
        CarbonImmutable $localStart,
        ?StaffProfile $staff,
        ?string $notes,
        ?Customer $existingCustomer = null,
        ?string $paymentMethod = null,
    ): Booking {
        $customer = $existingCustomer ?? $this->customers->handle($customerName, $phone, $email);
        $duration = (int) $service->duration_minutes;
        $buffer = (int) $service->buffer_minutes;

        $localEnd = $localStart->addMinutes($duration);
        $booking = Booking::query()->create([
            'tenant_id' => $this->currentTenant->idOrFail(),
            'customer_id' => $customer->getKey(),
            'service_id' => $service->getKey(),
            'staff_id' => $staff?->getKey(),
            'service_name_snapshot' => $service->name,
            'service_price_minor_snapshot' => (int) $service->price_minor,
            'service_currency_snapshot' => (string) $service->currency,
            'service_duration_minutes_snapshot' => $duration,
            'service_buffer_minutes_snapshot' => $buffer,
            'starts_at' => $localStart->setTimezone('UTC'),
            'ends_at' => $localEnd->setTimezone('UTC'),
            'block_ends_at' => $localEnd->addMinutes($buffer)->setTimezone('UTC'),
            'status' => BookingStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_method' => $paymentMethod,
            'booking_reference' => $this->reference(),
            'notes' => $notes,
        ]);

        $booking->statusHistory()->create([
            'from_status' => null,
            'to_status' => BookingStatus::Pending->value,
            'changed_by' => auth()->id(),
        ]);

        return $booking->fresh(['customer', 'service', 'staff', 'statusHistory']);
    }

    private function lockScope(int $tenantId, ?int $staffId, CarbonImmutable $localStart): void
    {
        $scope = $staffId === null
            ? sprintf('%d:business:%s', $tenantId, $localStart->toDateString())
            : sprintf('%d:staff:%d:%s', $tenantId, $staffId, $localStart->toDateString());

        DB::table('booking_locks')->insertOrIgnore([
            'tenant_id' => $tenantId,
            'scope_key' => $scope,
            'lock_date' => $localStart->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('booking_locks')
            ->where('scope_key', $scope)
            ->lockForUpdate()
            ->first();
    }

    private function reference(): string
    {
        do {
            $reference = 'BR-'.(string) random_int(100000, 999999);
        } while (Booking::withoutGlobalScopes()->where('booking_reference', $reference)->exists());

        return $reference;
    }

    private function timezone(): string
    {
        return (string) data_get(
            $this->currentTenant->get()?->profile,
            'timezone',
            config('app.timezone', 'UTC'),
        );
    }
}
