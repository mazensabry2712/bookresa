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
                ->get(),
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

            return to_route('staff.index')->with('status', __('Staff member added successfully.'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
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

            return to_route('staff.index')->with('status', __('Staff member updated successfully.'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }
}
