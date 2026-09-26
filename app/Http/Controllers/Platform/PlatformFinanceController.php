<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Payment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformFinanceController
{
    public function subscriptions(Request $request): View
    {
        $status = trim((string) $request->input('status'));

        return view('admin.subscriptions.index', [
            'subscriptions' => Subscription::withoutGlobalScopes()
                ->with(['tenant.profile', 'plan'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function payments(Request $request): View
    {
        $status = trim((string) $request->input('status'));
        $provider = trim((string) $request->input('provider'));

        return view('admin.payments.index', [
            'payments' => Payment::withoutGlobalScopes()
                ->with('tenant.profile')
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($provider !== '', fn ($query) => $query->where('provider', $provider))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'providers' => Payment::withoutGlobalScopes()
                ->whereNotNull('provider')
                ->distinct()
                ->orderBy('provider')
                ->pluck('provider'),
        ]);
    }

    public function usage(Request $request): View
    {
        $status = trim((string) $request->input('status'));

        return view('admin.usage.index', [
            'usagePeriods' => UsagePeriod::withoutGlobalScopes()
                ->with(['tenant.profile', 'subscription.plan'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
