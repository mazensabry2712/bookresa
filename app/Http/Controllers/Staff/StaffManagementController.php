<?php

namespace App\Http\Controllers\Staff;

use App\Domain\Service\Actions\SyncServiceAssignments;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Staff\Actions\UpdateStaffMember;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Staff\StoreStaffMemberRequest;
use App\Http\Requests\Staff\UpdateStaffMemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class StaffManagementController
{
    public function index(CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        return view('staff.index', [
            'tenant' => $currentTenant->get(),
            'staffMembers' => StaffProfile::query()
                ->with(['user', 'services'])
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'services' => Service::query()->orderBy('id')->get(),
            'roles' => array_values(array_filter(
                array_keys(config('bookresa.rbac.roles', [])),
                static fn (string $role): bool => $role !== 'owner',
            )),
        ]);
    }

    public function store(
        StoreStaffMemberRequest $request,
        AddStaffMember $addStaffMember,
        SyncServiceAssignments $syncServiceAssignments,
    ): RedirectResponse {
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        try {
            $staff = $addStaffMember->handle($user, $data['role'], [
                'display_name' => $data['display_name'] ?: $user->name,
                'phone' => $data['phone'] ?: null,
                'job_title' => $data['job_title'] ?: null,
            ]);

            $syncServiceAssignments->handle($staff, $data['services'] ?? []);

            return to_route('staff.index')->with('status', __('app.staff_ui.added'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }

    public function status(
        Request $request,
        StaffProfile $staff,
        UpdateStaffMember $updateStaffMember,
    ): RedirectResponse {
        $status = $request->validate(['status' => ['required', 'in:active,inactive']])['status'];

        try {
            $role = (string) ($staff->user->getRoleNames()->first() ?? 'staff');
            $updateStaffMember->handle($staff, [
                'display_name' => $staff->display_name,
                'phone' => $staff->phone,
                'job_title' => $staff->job_title,
                'role' => $role,
                'status' => $status,
            ]);

            return back()->with('status', __('app.staff_ui.status_updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()]);
        }
    }

    public function update(
        UpdateStaffMemberRequest $request,
        StaffProfile $staff,
        UpdateStaffMember $updateStaffMember,
        SyncServiceAssignments $syncServiceAssignments,
    ): RedirectResponse {
        try {
            $staff = $updateStaffMember->handle($staff, $request->validated());
            $syncServiceAssignments->handle($staff, $request->validated('services') ?? []);

            return to_route('staff.index')->with('status', __('app.staff_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }
}
