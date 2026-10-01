<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

final class PlatformAuditController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $tenantId = $request->integer('tenant_id') ?: null;
        $causerId = $request->integer('causer_id') ?: null;
        $from = $request->input('from');
        $to = $request->input('to');

        $activities = Activity::query()
            ->where('log_name', 'security')
            ->with(['causer', 'subject'])
            ->when($search !== '', fn ($query) => $query->where('description', 'like', '%'.$search.'%'))
            ->when($tenantId !== null, fn ($query) => $query->where('properties->tenant_id', $tenantId))
            ->when($causerId !== null, fn ($query) => $query->where('causer_id', $causerId))
            ->when($from !== null && $from !== '', fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to !== null && $to !== '', fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit.index', [
            'activities' => $activities,
            'tenants' => Tenant::query()
                ->with(['profile' => fn ($query) => $query->withoutGlobalScopes()])
                ->orderBy('id')
                ->get(),
            'actors' => User::query()
                ->whereIn(
                    'id',
                    PlatformAdmin::query()->select('user_id'),
                )
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }
}
