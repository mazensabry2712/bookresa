<?php

namespace App\Http\Controllers\Scheduling;

use App\Domain\Scheduling\Actions\AddBusinessBreak;
use App\Domain\Scheduling\Actions\AddStaffAvailability;
use App\Domain\Scheduling\Actions\AddStaffDayOff;
use App\Domain\Scheduling\Actions\RemoveBusinessBreak;
use App\Domain\Scheduling\Actions\RemoveBusinessHoliday;
use App\Domain\Scheduling\Actions\RemoveSpecialWorkingHour;
use App\Domain\Scheduling\Actions\RemoveStaffAvailability;
use App\Domain\Scheduling\Actions\RemoveStaffDayOff;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Actions\SetStaffWorkingHours;
use App\Domain\Scheduling\Actions\UpsertBusinessHoliday;
use App\Domain\Scheduling\Actions\UpsertSpecialWorkingHour;
use App\Domain\Scheduling\Models\BusinessBreak;
use App\Domain\Scheduling\Models\BusinessHoliday;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Scheduling\Models\SpecialWorkingHour;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Scheduling\StoreBusinessBreakRequest;
use App\Http\Requests\Scheduling\StoreBusinessHolidayRequest;
use App\Http\Requests\Scheduling\StoreSpecialWorkingHourRequest;
use App\Http\Requests\Scheduling\StoreStaffAvailabilityRequest;
use App\Http\Requests\Scheduling\StoreStaffDayOffRequest;
use App\Http\Requests\Scheduling\UpdateBusinessWorkingHoursRequest;
use App\Http\Requests\Scheduling\UpdateStaffWorkingHoursRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;

final class SchedulingManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $staffMembers = StaffProfile::query()
            ->with('user')
            ->orderBy('display_name')
            ->get();

        $selectedStaff = null;

        if ($request->filled('staff')) {
            $selectedStaff = StaffProfile::query()
                ->with(['workingHours', 'daysOff', 'availability'])
                ->findOrFail($request->integer('staff'));
        } elseif ($staffMembers->isNotEmpty()) {
            $selectedStaff = StaffProfile::query()
                ->with(['workingHours', 'daysOff', 'availability'])
                ->find($staffMembers->first()->getKey());
        }

        return view('scheduling.index', [
            'tenant' => $tenant,
            'businessHours' => BusinessWorkingHour::query()
                ->orderBy('day_of_week')
                ->get()
                ->keyBy(fn (BusinessWorkingHour $hour): int => $hour->day_of_week->value),
            'businessBreaks' => BusinessBreak::query()
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
            'businessHolidays' => BusinessHoliday::query()
                ->whereDate('holiday_date', '>=', now()->toDateString())
                ->orderBy('holiday_date')
                ->limit(30)
                ->get(),
            'specialWorkingHours' => SpecialWorkingHour::query()
                ->whereDate('work_date', '>=', now()->toDateString())
                ->orderBy('work_date')
                ->limit(30)
                ->get(),
            'staffMembers' => $staffMembers,
            'selectedStaff' => $selectedStaff,
            'selectedStaffHours' => $selectedStaff?->workingHours
                ->keyBy(fn (StaffWorkingHour $hour): int => $hour->day_of_week->value)
                ?? collect(),
            'selectedStaffDaysOff' => $selectedStaff?->daysOff
                ->sortBy('starts_on')
                ->values()
                ?? collect(),
            'selectedStaffAvailability' => $selectedStaff?->availability
                ->sortBy(fn (StaffAvailability $availability): string => $availability->available_date->format('Y-m-d').' '.(string) $availability->starts_at)
                ->values()
                ?? collect(),
        ]);
    }

    public function updateBusinessHours(
        UpdateBusinessWorkingHoursRequest $request,
        SetBusinessWorkingHours $action,
    ): RedirectResponse {
        try {
            $action->handle(array_values($request->validated('hours')));

            return to_route('scheduling.index')->with('status', __('Business working hours updated successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function storeBreak(
        StoreBusinessBreakRequest $request,
        AddBusinessBreak $action,
    ): RedirectResponse {
        try {
            $action->handle($request->validated());

            return to_route('scheduling.index')->with('status', __('Business break added successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function destroyBreak(
        BusinessBreak $break,
        RemoveBusinessBreak $action,
    ): RedirectResponse {
        try {
            $action->handle($break);

            return to_route('scheduling.index')->with('status', __('Business break removed successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function storeHoliday(
        StoreBusinessHolidayRequest $request,
        UpsertBusinessHoliday $action,
    ): RedirectResponse {
        try {
            $action->handle($request->validated());

            return to_route('scheduling.index')->with('status', __('Business holiday saved successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function destroyHoliday(
        BusinessHoliday $holiday,
        RemoveBusinessHoliday $action,
    ): RedirectResponse {
        try {
            $action->handle($holiday);

            return to_route('scheduling.index')->with('status', __('Business holiday removed successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function storeSpecialWorkingHour(
        StoreSpecialWorkingHourRequest $request,
        UpsertSpecialWorkingHour $action,
    ): RedirectResponse {
        try {
            $action->handle($request->validated());

            return to_route('scheduling.index')->with('status', __('Special working hours saved successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function destroySpecialWorkingHour(
        SpecialWorkingHour $specialWorkingHour,
        RemoveSpecialWorkingHour $action,
    ): RedirectResponse {
        try {
            $action->handle($specialWorkingHour);

            return to_route('scheduling.index')->with('status', __('Special working hours removed successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function updateStaffHours(
        UpdateStaffWorkingHoursRequest $request,
        StaffProfile $staff,
        SetStaffWorkingHours $action,
    ): RedirectResponse {
        try {
            $action->handle($staff, array_values($request->validated('hours')));

            return $this->staffRedirect($staff)->with('status', __('Staff working hours updated successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function storeDayOff(
        StoreStaffDayOffRequest $request,
        StaffProfile $staff,
        AddStaffDayOff $action,
    ): RedirectResponse {
        try {
            $action->handle($staff, $request->validated());

            return $this->staffRedirect($staff)->with('status', __('Staff day off added successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function destroyDayOff(
        StaffProfile $staff,
        StaffDayOff $dayOff,
        RemoveStaffDayOff $action,
    ): RedirectResponse {
        abort_unless((int) $dayOff->staff_id === (int) $staff->getKey(), 404);

        try {
            $action->handle($dayOff);

            return $this->staffRedirect($staff)->with('status', __('Staff day off removed successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function storeAvailability(
        StoreStaffAvailabilityRequest $request,
        StaffProfile $staff,
        AddStaffAvailability $action,
    ): RedirectResponse {
        try {
            $action->handle($staff, $request->validated());

            return $this->staffRedirect($staff)->with('status', __('Staff availability added successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    public function destroyAvailability(
        StaffProfile $staff,
        StaffAvailability $availability,
        RemoveStaffAvailability $action,
    ): RedirectResponse {
        abort_unless((int) $availability->staff_id === (int) $staff->getKey(), 404);

        try {
            $action->handle($availability);

            return $this->staffRedirect($staff)->with('status', __('Staff availability removed successfully.'));
        } catch (InvalidArgumentException|LogicException $exception) {
            return back()->withErrors(['schedule' => $exception->getMessage()])->withInput();
        }
    }

    private function staffRedirect(StaffProfile $staff): RedirectResponse
    {
        return to_route('scheduling.index', ['staff' => $staff->getKey()]);
    }
}
