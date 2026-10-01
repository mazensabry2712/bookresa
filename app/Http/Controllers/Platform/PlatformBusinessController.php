<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Customer\Models\Customer;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\TenantPaymentAccountStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Http\Requests\Platform\StorePlatformWorkspaceRequest;
use App\Http\Requests\Platform\UpdatePlatformWorkspaceRequest;
use App\Http\Requests\Platform\StoreWorkspaceMemberRequest;
use App\Http\Requests\Platform\UpdateWorkspaceMemberRequest;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Support\AuditLogger;
use Illuminate\Database\DatabaseManager;
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
                'paymentAccount' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->withCount([
                'memberships' => fn ($query) => $query->withoutGlobalScopes(),
                'services' => fn ($query) => $query->withoutGlobalScopes(),
                'staffProfiles' => fn ($query) => $query->withoutGlobalScopes(),
                'subscriptions' => fn ($query) => $query->withoutGlobalScopes(),
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

    public function create(): View
    {
        return view('admin.businesses.create', [
            'businessTypes' => BusinessType::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(
        StorePlatformWorkspaceRequest $request,
        CreateBusiness $createBusiness,
        DatabaseManager $database,
    ): RedirectResponse {
        $data = $request->validated();

        $tenant = $database->transaction(function () use ($data, $createBusiness): Tenant {
            $owner = User::query()->create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => $data['owner_password'],
                'email_verified_at' => now(),
            ]);

            $tenant = $createBusiness->handle(
                $owner,
                BusinessType::query()->findOrFail($data['business_type_id']),
                [
                    'name' => $data['business_name_en'],
                    'name_en' => $data['business_name_en'],
                    'name_ar' => $data['business_name_ar'] ?? $data['business_name_en'],
                    'slug' => $data['slug'] ?? $data['business_name_en'],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? $data['owner_email'],
                    'timezone' => $data['timezone'],
                    'locale' => $data['locale'],
                ],
            );

            if ($data['status'] !== TenantStatus::Active->value) {
                $tenant->forceFill(['status' => TenantStatus::from($data['status'])])->save();
            }

            app(AuditLogger::class)->log(
                'platform.workspace_created',
                $tenant,
                [
                    'tenant_id' => (int) $tenant->getKey(),
                    'owner_id' => (int) $owner->getKey(),
                ],
            );

            return $tenant;
        });

        return to_route('admin.businesses.show', $tenant)
            ->with('status', __('platform.workspace_created'));
    }

    public function edit(Tenant $tenant): View
    {
        $tenant->load([
            'businessType',
            'profile' => fn ($query) => $query->withoutGlobalScopes(),
        ]);

        return view('admin.businesses.edit', [
            'tenant' => $tenant,
            'businessTypes' => BusinessType::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function update(UpdatePlatformWorkspaceRequest $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validated();

        $tenant->forceFill([
            'slug' => $data['slug'],
            'business_type_id' => $data['business_type_id'],
            'status' => TenantStatus::from($data['status']),
        ])->save();

        $profile = BusinessProfile::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->firstOrCreate(
                ['tenant_id' => $tenant->getKey()],
                [
                    'name' => ['en' => $data['business_name_en'], 'ar' => $data['business_name_ar'] ?? $data['business_name_en']],
                    'timezone' => $data['timezone'],
                    'locale' => $data['locale'],
                ],
            );

        $profile->forceFill([
            'name' => [
                'en' => $data['business_name_en'],
                'ar' => $data['business_name_ar'] ?? $data['business_name_en'],
            ],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'timezone' => $data['timezone'],
            'locale' => $data['locale'],
        ])->save();

        app(AuditLogger::class)->log(
            'platform.workspace_updated',
            $tenant,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'status' => $tenant->status->value,
            ],
        );

        return to_route('admin.businesses.show', $tenant)
            ->with('status', __('platform.workspace_updated'));
    }

    public function show(Tenant $tenant): View
    {
        $tenantId = (int) $tenant->getKey();

        $tenant->load([
            'businessType',
            'profile' => fn ($query) => $query->withoutGlobalScopes(),
            'paymentAccount' => fn ($query) => $query->withoutGlobalScopes(),
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
                !(bool) $tenantModule->module?->is_core,
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
                ->where('payable_type', Booking::class)
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

        $memberRoles = [];
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenantId);
        try {
            foreach ($members as $membership) {
                $memberRoles[$membership->getKey()] = $membership->user?->getRoleNames()?->implode(', ') ?? '—';
            }
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        return view('admin.businesses.show', [
            'tenant' => $tenant,
            'owner' => $owner,
            'members' => $members,
            'memberRoles' => $memberRoles,
            'latestSubscription' => $latestSubscription,
            'subscriptionUsable' => $latestSubscription?->isUsable() === true,
            'usage' => $usage,
            'usagePercent' => $usagePercent,
            'activeModules' => $activeModules,
            'stats' => $stats,
            'recentBookings' => $recentBookings,
        ]);
    }

    public function addMember(
        StoreWorkspaceMemberRequest $request,
        Tenant $tenant,
        TenantRoleProvisioner $roleProvisioner,
    ): RedirectResponse {
        $data = $request->validated();

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null) {
            if (blank($data['password'] ?? null)) {
                throw ValidationException::withMessages([
                    'password' => __('A password is required when creating a new workspace member account.'),
                ]);
            }

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);
        } else {
            if ($user->name !== $data['name']) {
                $user->forceFill(['name' => $data['name']])->save();
            }

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $membership = $tenant->memberships()
            ->withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->first();

        if ($membership === null) {
            $makePrimary = (bool) ($data['is_primary'] ?? false)
                || ! $tenant->memberships()->withoutGlobalScopes()->where('is_primary', true)->exists();

            if ($makePrimary) {
                $tenant->memberships()
                    ->withoutGlobalScopes()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $membership = $tenant->memberships()->create([
                'user_id' => $user->getKey(),
                'status' => MembershipStatus::Active,
                'is_primary' => $makePrimary,
            ]);
        } else {
            $membership->forceFill(['status' => MembershipStatus::Active])->save();

            if ((bool) ($data['is_primary'] ?? false)) {
                $tenant->memberships()
                    ->withoutGlobalScopes()
                    ->where($membership->getKeyName(), '!=', $membership->getKey())
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);

                $membership->forceFill(['is_primary' => true])->save();
            }
        }

        $role = $roleProvisioner->provisionRole($tenant, $user);
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->getKey());
        try {
            $user->syncRoles([$role]);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(AuditLogger::class)->log(
            'platform.workspace_member_added',
            $membership,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'user_id' => (int) $user->getKey(),
                'role' => $data['role'],
                'is_primary' => (bool) $membership->is_primary,
            ],
        );

        return to_route('admin.businesses.show', $tenant)
            ->with('status', __('platform.member_added'));
    }

    public function updateMember(
        UpdateWorkspaceMemberRequest $request,
        Tenant $tenant,
        int $membership,
        TenantRoleProvisioner $roleProvisioner,
    ): RedirectResponse {
        $data = $request->validated();

        $membership = $tenant->memberships()
            ->withoutGlobalScopes()
            ->findOrFail($membership);

        $isPrimary = (bool) ($data['is_primary'] ?? false);

        if ($data['status'] === MembershipStatus::Suspended->value && $membership->is_primary) {
            throw ValidationException::withMessages([
                'status' => __('The workspace owner cannot be suspended. Promote another member first.'),
            ]);
        }

        if ($isPrimary) {
            $tenant->memberships()
                ->withoutGlobalScopes()
                ->where($membership->getKeyName(), '!=', $membership->getKey())
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        } elseif ($membership->is_primary && ! $isPrimary) {
            throw ValidationException::withMessages([
                'is_primary' => __('The workspace must always have a primary owner.'),
            ]);
        }

        $membership->forceFill([
            'status' => MembershipStatus::from($data['status']),
            'is_primary' => $isPrimary,
        ])->save();

        $role = $roleProvisioner->provisionRole($tenant, $membership->user);
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($tenant->getKey());
        try {
            $membership->user->syncRoles([$role]);
        } finally {
            setPermissionsTeamId($previousTeamId);
        }

        app(AuditLogger::class)->log(
            'platform.workspace_member_updated',
            $membership,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'user_id' => (int) $membership->user_id,
                'role' => $data['role'],
                'status' => $membership->status->value,
                'is_primary' => (bool) $membership->is_primary,
            ],
        );

        return back()->with('status', __('platform.member_updated'));
    }

    public function removeMember(Tenant $tenant, int $membership): RedirectResponse
    {
        $membership = $tenant->memberships()
            ->withoutGlobalScopes()
            ->findOrFail($membership);

        if ($membership->is_primary) {
            throw ValidationException::withMessages([
                'membership' => __('The primary workspace owner cannot be removed. Promote another member first.'),
            ]);
        }

        app(AuditLogger::class)->log(
            'platform.workspace_member_removed',
            $membership,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'user_id' => (int) $membership->user_id,
            ],
        );

        $membership->delete();

        return back()->with('status', __('platform.member_removed'));
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
            $account = new TenantPaymentAccount;
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

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenantId = (int) $tenant->getKey();

        app(AuditLogger::class)->log(
            'platform.workspace_deleted',
            $tenant,
            ['tenant_id' => $tenantId],
        );

        $tenant->delete();

        return to_route('admin.businesses.index')
            ->with('status', __('platform.workspace_deleted'));
    }
}
