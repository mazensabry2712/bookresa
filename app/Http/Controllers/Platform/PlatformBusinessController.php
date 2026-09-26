<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformBusinessController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $businesses = Tenant::query()
            ->with(['profile', 'businessType'])
            ->withCount(['memberships', 'services', 'staffProfiles'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('profile', function ($profile) use ($search): void {
                            $profile
                                ->where('name->en', 'like', '%'.$search.'%')
                                ->orWhere('name->ar', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.businesses.index', [
            'businesses' => $businesses,
        ]);
    }

    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $next = $tenant->status === TenantStatus::Suspended
            ? TenantStatus::Active
            : TenantStatus::Suspended;

        $tenant->forceFill(['status' => $next])->save();

        app(AuditLogger::class)->log(
            $next === TenantStatus::Suspended ? 'platform.business_suspended' : 'platform.business_activated',
            $tenant,
            ['tenant_id' => (int) $tenant->getKey(), 'status' => $next->value],
        );

        return back()->with(
            'status',
            $next === TenantStatus::Suspended
                ? __('Business suspended successfully.')
                : __('Business activated successfully.'),
        );
    }

}
