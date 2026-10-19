<?php

namespace App\Http\Controllers\Staff;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

final class StaffManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        return view('staff.index', [
            'tenant' => $currentTenant->get(),
            'staffMembers' => StaffProfile::query()
                ->with(['user', 'services'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $like = '%'.$search.'%';

                        $query
                            ->where('display_name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('job_title', 'like', $like)
                            ->orWhereHas('user', fn ($query) => $query->where('email', 'like', $like));
                    });
                })
                ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'services' => Service::query()->orderBy('id')->get(),
            'search' => $search,
            'statusFilter' => $validated['status'] ?? '',
            'roles' => array_values(array_filter(
                array_keys(config('bookresa.rbac.roles', [])),
                static fn (string $role): bool => $role !== 'owner',
            )),
        ]);
    }

    public function store(
        StoreStaffMemberRequest $request,
        Tenant $tenantRoute,
        AddStaffMember $addStaffMember,
        SyncServiceAssignments $syncServiceAssignments,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->firstOrFail();

        try {
            if (StaffProfile::query()->where('user_id', $user->getKey())->exists()) {
                return back()
                    ->withErrors(['email' => __('app.staff_ui.already_staff')])
                    ->withInput();
            }

            $staff = DB::transaction(function () use ($addStaffMember, $syncServiceAssignments, $user, $data): StaffProfile {
                $staff = $addStaffMember->handle($user, $data['role'], [
                    'display_name' => $data['display_name'] ?: $user->name,
                    'phone' => $data['phone'] ?: null,
                    'job_title' => $data['job_title'] ?: null,
                ]);

                return $syncServiceAssignments->handle($staff, $data['services'] ?? []);
            });

            $tenant = $currentTenant->get() ?? $tenantRoute;

            $onboardingCompleted = (bool) data_get($tenant->settings, 'onboarding.completed', false);

            if ($onboardingCompleted === false) {
                $coreModuleKeys = collect(config('bookresa.modules.core', []))
                    ->filter()
                    ->values();

                $coreModulesReady = $coreModuleKeys->isNotEmpty()
                    && $tenant->modules()
                        ->wherePivot('enabled', true)
                        ->whereIn('key', $coreModuleKeys)
                        ->count() === $coreModuleKeys->count();

                $hasServices = $tenant->services()->exists();
                $hasHours = BusinessWorkingHour::query()->where('is_closed', false)->exists();

                if ($coreModulesReady && $hasServices && $hasHours) {
                    $settings = $tenant->settings ?? [];
                    data_set($settings, 'onboarding.step', 'ready');
                    data_set($settings, 'onboarding.completed', true);
                    $tenant->forceFill(['settings' => $settings])->save();

                    $subscription = Subscription::query()
                        ->whereIn('status', [
                            SubscriptionStatus::Trial->value,
                            SubscriptionStatus::Active->value,
                        ])
                        ->latest('start_at')
                        ->get()
                        ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

                    return $subscription !== null
                        ? to_route('dashboard', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.added'))
                        : to_route('billing.subscription', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.added'));
                }

                $nextStep = $hasServices === false ? 'services' : ($hasHours === false ? 'hours' : 'staff');
                $settings = $tenant->settings ?? [];
                data_set($settings, 'onboarding.step', $nextStep);
                $tenant->forceFill(['settings' => $settings])->save();

                if ($hasServices === false) {
                    return to_route('services.index', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.added'));
                }

                if ($hasHours === false) {
                    return to_route('scheduling.index', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.added'));
                }
            }

            return to_route('staff.index', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.added'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }

    public function status(
        Request $request,
        Tenant $tenant,
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
        Tenant $tenant,
        StaffProfile $staff,
        UpdateStaffMember $updateStaffMember,
        SyncServiceAssignments $syncServiceAssignments,
    ): RedirectResponse {
        try {
            $data = $request->validated();

            DB::transaction(function () use ($updateStaffMember, $syncServiceAssignments, $staff, $data): void {
                $staff = $updateStaffMember->handle($staff, $data);

                $syncServiceAssignments->handle($staff, $data['services'] ?? []);
            });

            return to_route('staff.index', ['tenant' => $tenant->slug])->with('status', __('app.staff_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['staff' => $exception->getMessage()])->withInput();
        }
    }
}
