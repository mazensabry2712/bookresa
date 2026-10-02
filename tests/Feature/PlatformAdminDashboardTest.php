<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Enums\TenantPaymentAccountStatus;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

function adminDashboardUser(bool $admin = true): User
{
    $user = User::factory()->create();

    if ($admin) {
        PlatformAdmin::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    return $user;
}

function adminDashboardTenant(User $owner, string $name, TenantStatus $status = TenantStatus::Active): Tenant
{
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    if ($status !== TenantStatus::Active) {
        app(CurrentTenant::class)->run($tenant, function () use ($tenant, $status): void {
            $tenant->forceFill(['status' => $status])->save();
        });
    }

    return $tenant->fresh();
}

test('non platform admin cannot access platform dashboard or businesses', function (): void {
    $user = adminDashboardUser(false);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.businesses.index'))
        ->assertForbidden();

    $owner = User::factory()->create(['email' => 'non-admin-target@example.com']);
    $tenant = adminDashboardTenant($owner, 'Protected Workspace');

    $this->actingAs($user)
        ->get(route('admin.businesses.show', $tenant))
        ->assertForbidden();
});

test('platform admin can view dashboard metrics', function (): void {
    $admin = adminDashboardUser();

    $ownerA = User::factory()->create(['email' => 'admin-dashboard-owner-a@example.com']);
    $ownerB = User::factory()->create(['email' => 'admin-dashboard-owner-b@example.com']);

    adminDashboardTenant($ownerA, 'Admin Clinic A');
    adminDashboardTenant($ownerB, 'Admin Clinic B', TenantStatus::Suspended);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(__('platform.platform_command_center'))
        ->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['businesses'] === 2
                && $metrics['activeBusinesses'] === 1
                && $metrics['suspendedBusinesses'] === 1;
        })
        ->assertViewHas('recentBusinesses', function ($businesses): bool {
            return $businesses->count() === 2;
        });
});

test('platform admin can list and search businesses', function (): void {
    $admin = adminDashboardUser();

    $ownerA = User::factory()->create(['email' => 'search-owner-a@example.com']);
    $ownerB = User::factory()->create(['email' => 'search-owner-b@example.com']);

    adminDashboardTenant($ownerA, 'Alpha Dental');
    adminDashboardTenant($ownerB, 'Beta Salon');

    $this->actingAs($admin)
        ->get(route('admin.businesses.index'))
        ->assertOk()
        ->assertSee('Alpha Dental')
        ->assertSee('Beta Salon')
        ->assertSee('1');

    $this->actingAs($admin)
        ->get(route('admin.businesses.index', ['search' => 'Alpha']))
        ->assertOk()
        ->assertSee('Alpha Dental')
        ->assertDontSee('Beta Salon');
});

test('platform admin can suspend and reactivate a business', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'status-owner@example.com']);
    $tenant = adminDashboardTenant($owner, 'Status Clinic');

    $this->actingAs($admin)
        ->patch(route('admin.businesses.toggle-status', $tenant))
        ->assertRedirect()
        ->assertSessionHas('status', 'Business suspended successfully.');

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->actingAs($admin)
        ->patch(route('admin.businesses.toggle-status', $tenant))
        ->assertRedirect()
        ->assertSessionHas('status', 'Business activated successfully.');

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

test('platform admin can view subscriptions payments and usage', function (): void {
    $admin = adminDashboardUser();

    $this->actingAs($admin)
        ->get(route('admin.subscriptions.index'))
        ->assertOk()
        ->assertSee('Subscriptions');

    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('Payments');

    $this->actingAs($admin)
        ->get(route('admin.usage.index'))
        ->assertOk()
        ->assertSee('Usage');
});

test('platform admin can suspend and activate a subscription', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'subscription-admin-owner@example.com']);
    $tenant = adminDashboardTenant($owner, 'Subscription Admin Clinic');

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Professional', 'ar' => 'احترافي'],
        'price_minor' => 29900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle($plan);
    $subscription->forceFill(['payment_status' => PaymentStatus::Paid])->save();
    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->patch(route('admin.subscriptions.toggle-status', $subscription))
        ->assertRedirect()
        ->assertSessionHas('status', 'Subscription suspended successfully.');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Suspended);

    $this->actingAs($admin)
        ->patch(route('admin.subscriptions.toggle-status', $subscription->fresh()))
        ->assertRedirect()
        ->assertSessionHas('status', 'Subscription activated successfully.');

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('platform finance pages reject non platform admins', function (): void {
    $user = adminDashboardUser(false);

    $this->actingAs($user)
        ->get(route('admin.subscriptions.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.payments.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.usage.index'))
        ->assertForbidden();
});


test('platform admin can view a workspace control overview', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'workspace-owner@example.com']);
    $tenant = adminDashboardTenant($owner, 'Workspace Overview Clinic');

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Overview Plan', 'ar' => 'خطة النظرة العامة'],
        'price_minor' => 19900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle($plan);
    $subscription->forceFill(['payment_status' => PaymentStatus::Paid])->save();

    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('admin.businesses.show', $tenant))
        ->assertOk()
        ->assertSee('Workspace Overview Clinic')
        ->assertSee('workspace-owner@example.com')
        ->assertSee('Overview Plan')
        ->assertViewHas('stats', function (array $stats): bool {
            return $stats['customers'] === 0
                && $stats['bookings'] === 0
                && $stats['services'] === 0
                && $stats['staff'] === 0
                && $stats['activeMembers'] === 1
                && $stats['paidRevenueMinor'] === 0;
        })
        ->assertViewHas('subscriptionUsable', true);
});

test('platform admin can enter any workspace including suspended or disabled modules', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'super-admin-target@example.com']);
    $tenant = adminDashboardTenant($owner, 'Super Admin Target', TenantStatus::Suspended);

    app(CurrentTenant::class)->set($tenant);

    $servicesModule = \App\Domain\Module\Models\Module::query()
        ->where('key', 'services')
        ->firstOrFail();

    \App\Domain\Module\Models\TenantModule::query()
        ->where('tenant_id', $tenant->id)
        ->where('module_id', $servicesModule->id)
        ->update(['enabled' => false]);

    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('services.index', ['tenant' => $tenant->slug]))
        ->assertOk();
});

test('platform routes remain independent from current tenant context', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'independent-owner@example.com']);
    adminDashboardTenant($owner, 'Independent Clinic');

    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(app(CurrentTenant::class)->get())->toBeNull();
});


test('platform admin can activate a workspace booking payment account', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'connected-account-owner@example.com']);
    $tenant = adminDashboardTenant($owner, 'Connected Account Clinic');

    $this->actingAs($admin)
        ->patch(route('admin.businesses.payment-account.update', $tenant), [
            'merchant_id' => 'MID-123-ABC',
            'status' => 'active',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Workspace payment account updated successfully.');

    $account = TenantPaymentAccount::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    expect($account->merchant_id)->toBe('MID-123-ABC')
        ->and($account->status)->toBe(TenantPaymentAccountStatus::Active)
        ->and($account->connected_at)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.businesses.show', $tenant))
        ->assertOk()
        ->assertSee('MID-123-ABC')
        ->assertSee('Active');
});
