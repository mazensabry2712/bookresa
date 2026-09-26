<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformUserController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $users = User::query()
            ->with([
                'platformAdmin',
                'tenantMemberships.tenant.businessType',
                'tenantMemberships.tenant.profile' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->withCount([
                'tenantMemberships as active_memberships_count' => fn ($query) => $query->where('status', MembershipStatus::Active),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function toggleMembership(TenantMembership $membership): RedirectResponse
    {
        $next = $membership->status === MembershipStatus::Active
            ? MembershipStatus::Suspended
            : MembershipStatus::Active;

        $membership->forceFill(['status' => $next])->save();

        app(AuditLogger::class)->log(
            $next === MembershipStatus::Suspended
                ? 'platform.membership_suspended'
                : 'platform.membership_activated',
            $membership,
            [
                'membership_id' => (int) $membership->getKey(),
                'tenant_id' => (int) $membership->tenant_id,
                'user_id' => (int) $membership->user_id,
                'status' => $next->value,
            ],
        );

        return back()->with(
            'status',
            $next === MembershipStatus::Suspended
                ? __('User membership suspended successfully.')
                : __('User membership activated successfully.'),
        );
    }

    public function togglePlatformAdmin(User $user): RedirectResponse
    {
        $platformAdmin = PlatformAdmin::query()
            ->where('user_id', $user->getKey())
            ->first();

        if ($platformAdmin?->is_active) {
            if ((int) $user->getKey() === (int) auth()->id()) {
                return back()->withErrors([
                    'user' => __('You cannot deactivate your own platform admin access.'),
                ]);
            }

            if (PlatformAdmin::query()->where('is_active', true)->count() <= 1) {
                return back()->withErrors([
                    'user' => __('At least one active platform administrator is required.'),
                ]);
            }

            $platformAdmin->forceFill(['is_active' => false])->save();

            app(AuditLogger::class)->log(
                'platform.admin_deactivated',
                $platformAdmin,
                ['user_id' => (int) $user->getKey()],
            );

            return back()->with('status', __('Platform admin access deactivated successfully.'));
        }

        $platformAdmin ??= new PlatformAdmin(['user_id' => $user->getKey()]);
        $platformAdmin->forceFill(['is_active' => true])->save();

        app(AuditLogger::class)->log(
            'platform.admin_activated',
            $platformAdmin,
            ['user_id' => (int) $user->getKey()],
        );

        return back()->with('status', __('Platform admin access activated successfully.'));
    }
}
