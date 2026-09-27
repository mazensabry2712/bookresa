<?php

namespace App\Http\Controllers\Staff;

use App\Domain\Service\Actions\SyncServiceAssignments;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Staff\Actions\UpdateStaffMember;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Models\Tenant;
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
    public function index(CurrentTenant $currentTenant): RedirectResponse|View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

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
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        try {
            $staff = $addStaffMember->handle($user, $data['role'], [
                'display_name' => $data['display_name'] ?: $user->name,
                'phone' => $data['phone'] ?: null,
                'job_title' => $data['job_title'] ?: null,
            ]);

            $syncServiceAssignments->handle($staff, $data['services'] ?? []);

            $tenant = $currentTenant->get();

            if ($tenant !== null && ! (bool) data_get($tenant->settings, 'onboarding.completed', false)) {
                $settings = $tenant->settings ?? [];
                data_set($settings, 'onboarding.step', 'ready');
                $tenant->forceFill(['settings' => $settings])->save();

                return to_route('onboarding.workspace')->with('status', __('app.staff_ui.added'));
            }

            return to_route('staff.index')->with('status', __('app.staff_ui.added'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }

    public function status(
        Request $request,
        StaffProfile $staff,
        UpdateStaffMember $updateStaffMember,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

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
        CurrentTenant $currentTenant,
        StaffProfile $staff,
        UpdateStaffMember $updateStaffMember,
        SyncServiceAssignments $syncServiceAssignments,
        ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        try {
            $staff = $updateStaffMember->handle($staff, $request->validated());
            $syncServiceAssignments->handle($staff, $request->validated('services') ?? []);

            return to_route('staff.index')->with('status', __('app.staff_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }

    private function onboardingRedirect(Tenant $tenant): ?RedirectResponse
    {
        if ((bool) data_get($tenant->settings, 'onboarding.completed', false)) {
            return null;
        }

        $step = (string) data_get($tenant->settings, 'onboarding.step', 'workspace');

        return in_array($step, ['staff', 'ready'], true)
            ? null
            : to_route($step === 'services' ? 'services.index' : 'scheduling.index');
    }
}
