<?php

namespace App\Http\Controllers\Scheduling;

use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SchedulingManagementController
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function index(): View
    {
        $tenantId = $this->currentTenant->idOrFail();

        return view('scheduling.index', [
            'tenant' => $this->currentTenant->get(),
            'businessHours' => BusinessWorkingHour::query()
                ->orderBy('day_of_week')
                ->get()
                ->keyBy(fn (BusinessWorkingHour $hour): int => $hour->day_of_week->value),
            'businessBreaks' => BusinessBreak::query()
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
            'businessHolidays' => BusinessHoliday::query()
                ->orderBy('holiday_date')
                ->get(),
            'specialWorkingHours' => SpecialWorkingHour::query()
                ->orderBy('work_date')
                ->get(),
            'staffMembers' => StaffProfile::query()
                ->with([
                    'user',
                    'workingHours' => fn ($query) => $query->orderBy('day_of_week'),
                    'daysOff' => fn ($query) => $query->orderBy('starts_on'),
                    'availability' => fn ($query) => $query
                        ->orderBy('available_date')
                        ->orderBy('starts_at'),
                ])
                ->orderBy('display_name')
                ->get(),
            'tenantId' => $tenantId,
        ]);
    }

    public function updateBusinessHours(Request $request, SetBusinessWorkingHours $setBusinessWorkingHours): RedirectResponse
    {
        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['hours'] as $index => $hour) {
            $closed = (bool) ($hour['is_closed'] ?? false);

            if (! $closed && (! isset($hour['opens_at'], $hour['closes_at']) || $hour['closes_at'] <= $hour['opens_at'])) {
                return back()
                    ->withErrors(["hours.$index.closes_at" => 'Closing time must be later than opening time.'])
                    ->withInput();
            }
        }

        $setBusinessWorkingHours->handle($validated['hours']);

        return back()->with('status', 'Business working hours saved.');
    }

    public function storeBusinessBreak(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        BusinessBreak::query()->create($validated);

        return back()->with('status', 'Business break added.');
    }

    public function destroyBusinessBreak(BusinessBreak $break): RedirectResponse
    {
        $break->delete();

        return back()->with('status', 'Business break removed.');
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'holiday_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        BusinessHoliday::query()->updateOrCreate(
            ['holiday_date' => $validated['holiday_date']],
            ['reason' => $validated['reason'] ?? null],
        );

        return back()->with('status', 'Holiday saved.');
    }

    public function destroyHoliday(BusinessHoliday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('status', 'Holiday removed.');
    }

    public function storeSpecialWorkingHour(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'work_date' => ['required', 'date'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'is_closed' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $closed = (bool) ($validated['is_closed'] ?? false);

        if (! $closed && (! isset($validated['opens_at'], $validated['closes_at']) || $validated['closes_at'] <= $validated['opens_at'])) {
            return back()
                ->withErrors(['closes_at' => 'Closing time must be later than opening time.'])
                ->withInput();
        }

        SpecialWorkingHour::query()->updateOrCreate(
            ['work_date' => $validated['work_date']],
            [
                'opens_at' => $closed ? null : ($validated['opens_at'] ?? null),
                'closes_at' => $closed ? null : ($validated['closes_at'] ?? null),
                'is_closed' => $closed,
                'reason' => $validated['reason'] ?? null,
            ],
        );

        return back()->with('status', 'Special working hours saved.');
    }

    public function destroySpecialWorkingHour(SpecialWorkingHour $specialWorkingHour): RedirectResponse
    {
        $specialWorkingHour->delete();

        return back()->with('status', 'Special working hours removed.');
    }

    public function updateStaffHours(
        Request $request,
        StaffProfile $staff,
        SetStaffWorkingHours $setStaffWorkingHours,
    ): RedirectResponse {
        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['hours'] as $index => $hour) {
            $closed = (bool) ($hour['is_closed'] ?? false);

            if (! $closed && (! isset($hour['opens_at'], $hour['closes_at']) || $hour['closes_at'] <= $hour['opens_at'])) {
                return back()
                    ->withErrors(["hours.$index.closes_at" => 'Closing time must be later than opening time.'])
                    ->withInput();
            }
        }

        $setStaffWorkingHours->handle($staff, $validated['hours']);

        return back()->with('status', 'Staff working hours saved.');
    }

    public function storeStaffDayOff(Request $request, StaffProfile $staff): RedirectResponse
    {
        $validated = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $staff->daysOff()->create($validated);

        return back()->with('status', 'Staff day off added.');
    }

    public function destroyStaffDayOff(StaffDayOff $dayOff): RedirectResponse
    {
        $dayOff->delete();

        return back()->with('status', 'Staff day off removed.');
    }

    public function storeStaffAvailability(Request $request, StaffProfile $staff): RedirectResponse
    {
        $validated = $request->validate([
            'available_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ]);

        $staff->availability()->create($validated);

        return back()->with('status', 'Staff availability added.');
    }

    public function destroyStaffAvailability(StaffAvailability $availability): RedirectResponse
    {
        $availability->delete();

        return back()->with('status', 'Staff availability removed.');
    }
}
