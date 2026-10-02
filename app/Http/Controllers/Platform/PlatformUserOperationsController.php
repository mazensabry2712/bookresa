<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PlatformUserOperationsController
{
    public function show(User $user): View
    {
        $user->load([
            'platformAdmin',
            'tenantMemberships.tenant.profile',
            'tenantMemberships.tenant.businessType',
        ]);

        return view('admin.users.show', [
            'user' => $user,
            'platformAdmin' => $user->platformAdmin,
            'platformRoles' => config('platform.roles', []),
            'platformPermissions' => config('platform.permissions', []),
            'securityEvents' => \Spatie\Activitylog\Models\Activity::query()
                ->where('log_name', 'security')
                ->where('causer_id', $user->getKey())
                ->latest('id')
                ->limit(30)
                ->get(),
        ]);
    }

    public function updateProfile(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->getKey()],
        ]);

        $emailChanged = strtolower($user->email) !== strtolower($validated['email']);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ])->save();

        $audit->log('platform.user_profile_updated', $user, [
            'user_id' => (int) $user->getKey(),
            'email_changed' => $emailChanged,
        ]);

        return back()->with('status', __('User profile updated successfully.'));
    }

    public function resetPassword(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $this->revokeSessionsFor($user);

        $audit->log('platform.user_password_reset', $user, [
            'user_id' => (int) $user->getKey(),
        ]);

        return back()->with('status', __('User password reset and active sessions revoked.'));
    }

    public function forceVerify(User $user, AuditLogger $audit): RedirectResponse
    {
        $user->forceFill(['email_verified_at' => now()])->save();

        $audit->log('platform.user_email_verified', $user, [
            'user_id' => (int) $user->getKey(),
        ]);

        return back()->with('status', __('User email marked as verified.'));
    }

    public function revokeSessions(User $user, AuditLogger $audit): RedirectResponse
    {
        $this->revokeSessionsFor($user);

        $audit->log('platform.user_sessions_revoked', $user, [
            'user_id' => (int) $user->getKey(),
        ]);

        return back()->with('status', __('All active sessions for the user were revoked.'));
    }

    public function updatePlatformAdminAccess(
        Request $request,
        User $user,
        AuditLogger $audit,
    ): RedirectResponse {
        $admin = PlatformAdmin::query()->firstOrNew(['user_id' => $user->getKey()]);
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:'.implode(',', array_keys(config('platform.roles', [])))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:'.implode(',', config('platform.permissions', []))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $wantActive = $request->boolean('is_active');

        if ((int) $user->getKey() === (int) $request->user()->getKey() && ! $wantActive) {
            throw ValidationException::withMessages([
                'is_active' => __('You cannot deactivate your own platform admin access.'),
            ]);
        }

        if ($admin->is_active && ! $wantActive && PlatformAdmin::query()->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => __('At least one active platform administrator is required.'),
            ]);
        }

        if (
            $admin->is_active
            && $admin->role === 'super_admin'
            && $validated['role'] !== 'super_admin'
            && PlatformAdmin::query()
                ->where('is_active', true)
                ->where('role', 'super_admin')
                ->count() <= 1
            && $wantActive
        ) {
            throw ValidationException::withMessages([
                'role' => __('At least one active Super Admin is required.'),
            ]);
        }

        $admin->forceFill([
            'role' => $validated['role'],
            'permissions' => array_values(array_unique($validated['permissions'] ?? [])),
            'is_active' => $wantActive,
        ])->save();

        $audit->log(
            $wantActive ? 'platform.admin_access_updated' : 'platform.admin_deactivated',
            $admin,
            [
                'user_id' => (int) $user->getKey(),
                'role' => $admin->role,
                'permissions' => $admin->permissions ?? [],
                'is_active' => (bool) $admin->is_active,
            ],
        );

        return back()->with('status', __('Platform administrator access updated successfully.'));
    }

    private function revokeSessionsFor(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->delete();
        }
    }
}
