<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Billing\Services\PlanCatalog;
use App\Domain\Billing\Services\UpsertPlan;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function billingDashboardTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create(['slug' => $slug, 'status' => TenantStatus::Active]);
    app(CurrentTenant::class)->set($tenant);
    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'timezone' => 'Africa/Cairo',
        'email' => $slug.'@example.com',
    ]);
    return $tenant;
}

function billingDashboardUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);
    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);
    setPermissionsTeamId($tenant->id);
    foreach (['billing.view', 'subscription.manage'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
    $role = Role::firstOrCreate(['name' => 'billing-owner', 'guard_name' => 'web', 'tenant_id' => $tenant->id]);
    $role->syncPermissions(['billing.view', 'subscription.manage']);
    $user->assignRole($role);

    return $user;
}

function billingDashboardPlan(array $overrides = []): Plan
{
    return Plan::query()->create(array_merge([
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter'],
        'price_minor' => 19900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 2,
        'additional_customer_price_minor' => 50000,
        'trial_days' => 0,
        'is_active' => true,
    ], $overrides));
}

test('owner can see live customer usage and estimated billing total', function (): void {
    $tenant = billingDashboardTenant('usage-dashboard');
    $user = billingDashboardUser($tenant, 'usage@example.com');
    $plan = billingDashboardPlan();
    $subscription = app(CreateSubscription::class)->handle($plan, CarbonImmutable::parse('2026-10-01', 'UTC'));
    Customer::factory()->count(4)->create();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertOk()
        ->assertSee('4')
        ->assertSee('1,000.00 EGP')
        ->assertSee('1,000.00 EGP')
        ->assertSee('1,199.00 EGP');

    expect($subscription->fresh()->isUsable())->toBeFalse();
});

test('owner can schedule a plan change and clear it', function (): void {
    $tenant = billingDashboardTenant('plan-change-dashboard');
    $user = billingDashboardUser($tenant, 'change@example.com');
    $current = billingDashboardPlan();
    $next = billingDashboardPlan(['name' => ['en' => 'Business', 'ar' => 'أعمال'], 'price_minor' => 39900]);
    $subscription = app(CreateSubscription::class)->handle($current, CarbonImmutable::parse('2026-10-01', 'UTC'));

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('billing.subscription.plan', $subscription), ['plan_id' => $next->id])
        ->assertSessionHas('status');

    expect($subscription->fresh()->next_plan_id)->toBe($next->id);

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('billing.subscription.plan.clear', $subscription))
        ->assertSessionHas('status');

    expect($subscription->fresh()->next_plan_id)->toBeNull();
});

test('owner can schedule cancellation and reactivate it', function (): void {
    $tenant = billingDashboardTenant('cancel-dashboard');
    $user = billingDashboardUser($tenant, 'cancel@example.com');
    $subscription = app(CreateSubscription::class)->handle(billingDashboardPlan(), CarbonImmutable::parse('2026-10-01', 'UTC'));

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('billing.subscription.cancel', $subscription))->assertSessionHas('status');

    expect($subscription->fresh()->cancelled_at)->not->toBeNull();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('billing.subscription.reactivate', $subscription))->assertSessionHas('status');

    expect($subscription->fresh()->cancelled_at)->toBeNull();
});

test('owner cannot mutate another tenant subscription through billing actions', function (): void {
    $tenantA = billingDashboardTenant('owner-a');
    $userA = billingDashboardUser($tenantA, 'owner-a@example.com');
    $subscriptionA = app(CreateSubscription::class)->handle(billingDashboardPlan(), CarbonImmutable::parse('2026-10-01', 'UTC'));

    $tenantB = billingDashboardTenant('owner-b');
    $subscriptionB = app(CreateSubscription::class)->handle(billingDashboardPlan(), CarbonImmutable::parse('2026-10-01', 'UTC'));

    $this->actingAs($userA)->withSession(['tenant_id' => $tenantA->id])
        ->post(route('billing.subscription.cancel', $subscriptionB))
        ->assertNotFound();

    expect($subscriptionA->fresh()->cancelled_at)->toBeNull();
});

test('billing payment history is bounded to the latest 20 records', function (): void {
    $tenant = billingDashboardTenant('payment-history-dashboard');
    $user = billingDashboardUser($tenant, 'payment-history@example.com');
    $subscription = app(CreateSubscription::class)->handle(
        billingDashboardPlan(),
        CarbonImmutable::parse('2026-10-01', 'UTC'),
    );

    for ($i = 1; $i <= 21; $i++) {
        Payment::query()->create([
            'tenant_id' => $tenant->id,
            'payable_type' => $subscription->getMorphClass(),
            'payable_id' => $subscription->id,
            'reference' => 'SUB-PAY-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'provider' => 'kashier',
            'amount_minor' => 19900,
            'currency' => 'EGP',
            'status' => PaymentStatus::Pending,
            'idempotency_key' => 'payment-history-'.$i,
        ]);
    }

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertOk()
        ->assertSee('SUB-PAY-21')
        ->assertDontSee('SUB-PAY-01');
});

test('active plan catalog is invalidated when a plan changes', function (): void {
    $tenant = billingDashboardTenant('plan-catalog-cache');
    $current = billingDashboardPlan();
    app(PlanCatalog::class)->active();

    $next = app(UpsertPlan::class)->handle(null, [
        'name' => ['en' => 'Business', 'ar' => 'أعمال'],
        'description' => ['en' => 'Business', 'ar' => 'أعمال'],
        'price_minor' => 39900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly->value,
        'included_customer_limit' => 50,
        'additional_customer_price_minor' => 800,
        'trial_days' => 7,
        'is_active' => true,
    ]);

    expect(app(PlanCatalog::class)->active()->pluck('id')->all())
        ->toContain($current->id)
        ->toContain($next->id);
});
