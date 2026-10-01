<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class PlatformImpersonationController
{
    public function start(
        Request $request,
        Tenant $tenant,
        User $user,
        AuditLogger $audit,
    ): RedirectResponse {
        abort_if((int) $request->user()->getKey() === (int) $user->getKey(), 422);

        abort_if(
            PlatformAdmin::query()
                ->where('user_id', $user->getKey())
                ->where('is_active', true)
                ->exists(),
            403,
            'Platform administrators cannot be impersonated.',
        );

        $membership = $tenant->memberships()
            ->withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->where('status', MembershipStatus::Active)
            ->with('user')
            ->first();

        if ($membership === null) {
            throw ValidationException::withMessages([
                'user' => __('platform.impersonation_member_required'),
            ]);
        }

        if ($user->email_verified_at === null) {
            throw ValidationException::withMessages([
                'user' => __('platform.impersonation_verified_required'),
            ]);
        }

        $adminId = (int) $request->user()->getKey();

        $audit->log(
            'platform.impersonation_started',
            $user,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'impersonated_user_id' => (int) $user->getKey(),
            ],
        );

        $request->session()->put([
            'platform_impersonator_id' => $adminId,
            'platform_impersonated_user_id' => (int) $user->getKey(),
            'platform_impersonated_tenant_id' => (int) $tenant->getKey(),
            'platform_impersonation_started_at' => now()->toIso8601String(),
            'tenant_id' => (int) $tenant->getKey(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard', ['tenant' => $tenant->slug]);
    }

    public function stop(Request $request, AuditLogger $audit): RedirectResponse
    {
        $adminId = (int) $request->session()->get('platform_impersonator_id', 0);
        $impersonatedUserId = (int) $request->session()->get('platform_impersonated_user_id', 0);
        $tenantId = (int) $request->session()->get('platform_impersonated_tenant_id', 0);

        abort_unless($adminId > 0 && $impersonatedUserId > 0 && $tenantId > 0, 403);

        $admin = User::query()->findOrFail($adminId);

        abort_unless(
            PlatformAdmin::query()
                ->where('user_id', $admin->getKey())
                ->where('is_active', true)
                ->exists(),
            403,
            'The original platform administrator is no longer active.',
        );

        $currentUser = $request->user();

        Auth::login($admin);
        $request->session()->forget([
            'platform_impersonator_id',
            'platform_impersonated_user_id',
            'platform_impersonated_tenant_id',
            'platform_impersonation_started_at',
            'tenant_id',
        ]);
        $request->session()->regenerate();

        $audit->log(
            'platform.impersonation_stopped',
            $currentUser,
            [
                'tenant_id' => $tenantId,
                'impersonated_user_id' => $impersonatedUserId,
                'platform_admin_id' => $adminId,
            ],
        );

        return to_route('admin.dashboard');
    }
}
