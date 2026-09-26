<?php

namespace App\Http\Controllers\Booking;

use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Booking\Services\RescheduleBooking;
use App\Domain\Staff\Models\StaffProfile;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class BookingManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,completed,cancelled,rescheduled,no_show'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff_profiles,id'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $timezone = (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC'));
        $staffUserId = $request->user()?->hasRole('staff') ? $request->user()->getKey() : null;
        $ownStaffId = $staffUserId === null ? null : StaffProfile::query()->where('user_id', $staffUserId)->value('id');

        $bookings = Booking::query()
            ->with([
                'customer:id,name,phone,email',
                'service:id,name,price_minor,currency,duration_minutes',
                'staff:id,display_name',
            ])
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('booking_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['service_id'] ?? null, fn ($query, int $serviceId) => $query->where('service_id', $serviceId))
            ->when($ownStaffId !== null, fn ($query) => $query->where('staff_id', $ownStaffId))
            ->when($ownStaffId === null, function ($query) use ($staffUserId): void {
                if ($staffUserId !== null) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($ownStaffId === null, fn ($query) => $query->when($validated['staff_id'] ?? null, fn ($query, int $staffId) => $query->where('staff_id', $staffId)))
            ->when($validated['date'] ?? null, function ($query, string $date) use ($timezone): void {
                $start = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00', $timezone);
                $end = $start->endOfDay();

                $start = $start->utc();
                $end = $end->utc();

                $query->whereBetween('starts_at', [$start, $end]);
            })
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('booking.management.index', [
            'tenant' => $tenant,
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
            'services' => Service::query()
                ->orderBy('id')
                ->get(['id', 'name']),
            'staff' => StaffProfile::query()
                ->orderBy('display_name')
                ->get(['id', 'display_name']),
            'timezone' => $timezone,
        ]);
    }

    public function show(Booking $booking, CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        $booking->load([
            'customer',
            'service',
            'staff',
            'statusHistory' => fn ($query) => $query->with('changedBy')->latest('created_at'),
        ]);

        return view('booking.management.show', [
            'tenant' => $currentTenant->get(),
            'booking' => $booking,
            'timezone' => (string) data_get($currentTenant->get()->profile, 'timezone', config('app.timezone', 'UTC')),
            'staffMembers' => StaffProfile::query()->where('status', 'active')->orderBy('display_name')->get(['id', 'display_name']),
        ]);
    }

    public function reschedule(
        RescheduleBookingRequest $request,
        Booking $booking,
        RescheduleBooking $rescheduleBooking,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        try {
            $tenant = $currentTenant->get();
            $timezone = (string) data_get($tenant?->profile, 'timezone', config('app.timezone', 'UTC'));
            $staff = $request->filled('staff_id')
                ? StaffProfile::query()->findOrFail($request->integer('staff_id'))
                : null;
            $startsAt = CarbonImmutable::createFromFormat(
                'Y-m-d H:i',
                $request->validated('date').' '.$request->validated('time'),
                $timezone,
            );

            $rescheduleBooking->handle($booking, $startsAt, $staff);

            return to_route('booking.management.show', $booking)->with('status', __('Booking rescheduled successfully.'));
        } catch (RuntimeException|\LogicException $exception) {
            return back()->withErrors(['reschedule' => $exception->getMessage()])->withInput();
        }
    }

    public function status(
        UpdateBookingStatusRequest $request,
        Booking $booking,
        UpdateBookingStatus $updateBookingStatus,
    ): RedirectResponse {
        $status = BookingStatus::from($request->validated('status'));

        $permission = match ($status) {
            BookingStatus::Cancelled => 'bookings.cancel',
            BookingStatus::Completed, BookingStatus::NoShow => 'bookings.complete',
            default => 'bookings.update',
        };

        abort_unless($request->user()?->can($permission), 403);

        try {
            $updateBookingStatus->handle(
                $booking,
                $status,
                $request->validated('reason'),
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return to_route('booking.management.show', $booking)
            ->with('status', 'Booking status updated successfully.');
    }
}
