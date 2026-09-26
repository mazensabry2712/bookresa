<?php

namespace App\Http\Controllers\Calendar;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CalendarController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'view' => ['nullable', 'in:day,week,month'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff_profiles,id'],
            'status' => ['nullable', 'string', 'in:pending,confirmed,completed,cancelled,rescheduled,no_show'],
        ]);

        $timezone = (string) data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC'));
        $viewMode = $validated['view'] ?? 'month';
        $baseDate = $validated['date']
            ?? (isset($validated['month']) ? $validated['month'].'-01' : CarbonImmutable::now($timezone)->format('Y-m-d'));

        try {
            $reference = CarbonImmutable::createFromFormat('Y-m-d', $baseDate, $timezone)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['date' => 'Invalid calendar date.']);
        }

        [$gridStart, $gridEnd] = match ($viewMode) {
            'day' => [$reference, $reference],
            'week' => [$reference->startOfWeek(CarbonImmutable::MONDAY), $reference->endOfWeek(CarbonImmutable::SUNDAY)],
            default => [$reference->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY), $reference->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)],
        };

        $periodStart = $viewMode === 'month' ? $reference->startOfMonth() : $gridStart;
        $periodEnd = $viewMode === 'month' ? $reference->endOfMonth() : $gridEnd;

        $rangeStartUtc = $gridStart->utc();
        $rangeEndUtc = $gridEnd->addSecond()->utc();

        $bookings = Booking::query()
            ->with([
                'customer:id,name,phone',
                'service:id,name,price_minor,currency,duration_minutes',
                'staff:id,display_name',
            ])
            ->when($validated['service_id'] ?? null, fn ($query, int $serviceId) => $query->where('service_id', $serviceId))
            ->when($validated['staff_id'] ?? null, fn ($query, int $staffId) => $query->where('staff_id', $staffId))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->where('starts_at', '<', $rangeEndUtc)
            ->where('ends_at', '>', $rangeStartUtc)
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $calendarDays = [];
        for ($date = $gridStart; $date->lte($gridEnd); $date = $date->addDay()) {
            $calendarDays[] = $date;
        }

        $bookingsByDate = $bookings->groupBy(
            fn (Booking $booking): string => $booking->starts_at->setTimezone($timezone)->toDateString()
        );

        return view('calendar.index', [
            'tenant' => $tenant,
            'timezone' => $timezone,
            'month' => $reference->startOfMonth(),
            'previousMonth' => $reference->startOfMonth()->subMonth()->format('Y-m'),
            'nextMonth' => $reference->startOfMonth()->addMonth()->format('Y-m'),
            'today' => CarbonImmutable::now($timezone)->toDateString(),
            'viewMode' => $viewMode,
            'reference' => $reference,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'previousDate' => match ($viewMode) { 'day' => $reference->subDay(), 'week' => $reference->subWeek(), default => $reference->subMonth() },
            'nextDate' => match ($viewMode) { 'day' => $reference->addDay(), 'week' => $reference->addWeek(), default => $reference->addMonth() },
            'calendarDays' => $calendarDays,
            'bookingsByDate' => $bookingsByDate,
            'statuses' => BookingStatus::cases(),
            'services' => Service::query()->orderBy('id')->get(['id', 'name']),
            'staff' => StaffProfile::query()->orderBy('display_name')->get(['id', 'display_name']),
        ]);
    }
}
