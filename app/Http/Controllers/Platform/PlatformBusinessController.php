<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Enums\TenantPaymentAccountStatus;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
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
            ->with([
                'profile' => fn ($query) => $query->withoutGlobalScopes(),
                'businessType',
            'paymentAccount',
            ])
            ->withCount([
                'memberships' => fn ($query) => $query->withoutGlobalScopes(),
                'services' => fn ($query) => $query->withoutGlobalScopes(),
                'staffProfiles' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('slug', 'like', '%'.$search.'%')
                        ->orWhereHas('profile', function ($profile) use ($search): void {
                            $profile->withoutGlobalScopes();
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

    public function show(Tenant $tenant): View
    {
        $tenantId = (int) $tenant->getKey();

        $tenant->load([
            'businessType',
            'profile' => fn ($query) => $query->withoutGlobalScopes(),
        ]);

        $members = $tenant->memberships()
            ->withoutGlobalScopes()
            ->with('user')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $owner = $members->first(fn ($membership): bool => $membership->is_primary)
            ?? $members->first();

        $latestSubscription = Subscription::withoutGlobalScopes()
            ->with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('start_at')
            ->first();

        $usage = UsagePeriod::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->latest('period_start')
            ->first();

        $activeModules = TenantModule::withoutGlobalScopes()
            ->with('module')
            ->where('tenant_id', $tenantId)
            ->where('enabled', true)
            ->whereHas('module', fn ($query) => $query->where('is_active', true))
            ->get()
            ->sortBy(fn ($tenantModule) => [
                ! (bool) $tenantModule->module?->is_core,
                (int) $tenantModule->module_id,
            ])
            ->values();

        $stats = [
            'customers' => Customer::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'bookings' => Booking::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'services' => Service::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'staff' => StaffProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)->count(),
            'activeMembers' => $tenant->memberships()
                ->withoutGlobalScopes()
                ->where('status', MembershipStatus::Active)
                ->count(),
            'paidRevenueMinor' => Payment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', PaymentStatus::Paid)
                ->sum('amount_minor'),
        ];

        $recentBookings = Booking::withoutGlobalScopes()
            ->with([
                'customer' => fn ($query) => $query->withoutGlobalScopes(),
                'service' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->where('tenant_id', $tenantId)
            ->latest('starts_at')
            ->limit(5)
            ->get();

        $usagePercent = $usage !== null && $usage->included_customer_limit > 0
            ? min(100, (int) round(($usage->unique_customer_count / $usage->included_customer_limit) * 100))
            : 0;

        return view('admin.businesses.show', [
            'tenant' => $tenant,
            'owner' => $owner,
            'members' => $members,
            'latestSubscription' => $latestSubscription,
            'subscriptionUsable' => $latestSubscription?->isUsable() === true,
            'usage' => $usage,
            'usagePercent' => $usagePercent,
            'activeModules' => $activeModules,
            'stats' => $stats,
            'recentBookings' => $recentBookings,
        ]);
    }

    public function updatePaymentAccount(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'regex:/^MID-[A-Z0-9-]+$/', 'max:80'],
            'status' => ['required', 'in:pending,active,disabled'],
        ]);

        $account = TenantPaymentAccount::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->first();

        if ($account === null) {
            $account = new TenantPaymentAccount();
            $account->forceFill([
                'tenant_id' => $tenant->getKey(),
                'provider' => (string) config('bookresa.payments.default_provider', 'kashier'),
                'merchant_id' => $validated['merchant_id'],
                'status' => $validated['status'],
                'connected_at' => $validated['status'] === TenantPaymentAccountStatus::Active->value ? now() : null,
                'metadata' => ['connection_source' => 'platform_admin'],
            ]);
            $account->saveQuietly();
        } else {
            abort_unless((int) $account->tenant_id === (int) $tenant->getKey(), 409);

            $account->forceFill([
                'merchant_id' => $validated['merchant_id'],
                'status' => $validated['status'],
                'connected_at' => $validated['status'] === TenantPaymentAccountStatus::Active->value ? now() : null,
            ])->saveQuietly();
        }

        app(AuditLogger::class)->log(
            'platform.workspace_payment_account_updated',
            $account,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'merchant_id' => $account->merchant_id,
                'status' => $account->status->value,
            ],
        );

        return back()->with('status', __('Workspace payment account updated successfully.'));
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
