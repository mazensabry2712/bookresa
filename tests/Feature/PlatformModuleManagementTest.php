<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Platform\Models\PlatformAdmin;
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

function platformModulesAdmin(): User
{
    $user = User::factory()->create();

    PlatformAdmin::query()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $user;
}

function platformModulesTenant(User $owner, string $name): Tenant
{
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    return app(CreateBusiness::class)->handle($owner, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);
}

function platformModulesSubscription(Tenant $tenant, Module $module): Subscription
{
    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Module Plan', 'ar' => 'خطة الوحدات'],
        'description' => ['en' => 'Module plan'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 14,
        'is_active' => true,
    ]);
    $plan->modules()->sync([$module->id]);

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle($plan);

    app(CurrentTenant::class)->clear();

    return $subscription;
}

test('non platform admin cannot access workspace module management', function (): void {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $tenant = platformModulesTenant($owner, 'Module Clinic');

    $this->actingAs($user)
        ->get(route('admin.businesses.modules.index', $tenant))
        ->assertForbidden();
});

test('platform admin can view workspace modules', function (): void {
    $admin = platformModulesAdmin();
    $owner = User::factory()->create();
    $tenant = platformModulesTenant($owner, 'Module Clinic');

    $this->actingAs($admin)
        ->get(route('admin.businesses.modules.index', $tenant))
        ->assertOk()
        ->assertSee('Module Clinic')
        ->assertSee('Appointments')
        ->assertSee('Payments')
        ->assertSee('Not included in plan');
});

test('platform admin can update core and entitled optional modules', function (): void {
    $admin = platformModulesAdmin();
    $owner = User::factory()->create();
    $tenant = platformModulesTenant($owner, 'Module Clinic');
    $appointments = Module::query()->where('key', 'appointments')->firstOrFail();
    $payments = Module::query()->where('key', 'payments')->firstOrFail();

    platformModulesSubscription($tenant, $payments);

    $this->actingAs($admin)
        ->put(route('admin.businesses.modules.update', $tenant), [
            'module_ids' => [$appointments->id, $payments->id],
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Workspace modules updated successfully.');

    expect(TenantModule::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('module_id', $appointments->id)->value('enabled'))->toBeTrue()
        ->and(TenantModule::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('module_id', $payments->id)->value('enabled'))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('admin.businesses.modules.update', $tenant), [
            'module_ids' => [],
        ])
        ->assertRedirect();

    expect(TenantModule::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('module_id', $appointments->id)->value('enabled'))->toBeFalse()
        ->and(TenantModule::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('module_id', $payments->id)->value('enabled'))->toBeFalse();
});

test('platform admin cannot enable optional module outside subscription entitlement', function (): void {
    $admin = platformModulesAdmin();
    $owner = User::factory()->create();
    $tenant = platformModulesTenant($owner, 'Module Clinic');
    $payments = Module::query()->where('key', 'payments')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.businesses.modules.update', $tenant), [
            'module_ids' => [$payments->id],
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('module_ids');

    expect(TenantModule::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('module_id', $payments->id)->exists())->toBeFalse();
});
