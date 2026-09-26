<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);

    Route::middleware(['web', 'auth', 'tenant', 'module:payments'])
        ->get('/__test/optional-payments-module', fn () => 'ok');
});

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function moduleWorkspace(string $name = 'Module Clinic'): array
{
    $owner = User::factory()->create([
        'name' => $name.' Owner',
    ]);

    $businessType = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $businessType, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$owner, $tenant];
}

test('enabled modules allow authorized tenant routes', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index'))
        ->assertOk();
});

test('core service route remains available when the module catalog is not seeded', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    Module::query()->where('key', 'services')->delete();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk();
});

test('disabled service module blocks service routes even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'services')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store'), [
            'name_en' => 'Blocked',
            'name_ar' => 'محجوبة',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertForbidden();
});

test('disabled staff module blocks staff routes even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'staff')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index'))
        ->assertForbidden();
});

test('disabled calendar module blocks calendar route even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'calendar')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index'))
        ->assertForbidden();
});

test('disabled appointments module blocks booking management and public booking', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'appointments')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index'))
        ->assertForbidden();

    $this->get(route('public.booking.show', ['tenant' => $tenant->slug]))
        ->assertForbidden();
});

test('missing optional tenant module row does not grant access', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/__test/optional-payments-module')
        ->assertForbidden();
});

test('enabled optional module requires subscription entitlement', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    $payments = Module::query()->where('key', 'payments')->firstOrFail();

    app(CurrentTenant::class)->set($tenant);

    TenantModule::query()->create([
        'tenant_id' => $tenant->id,
        'module_id' => $payments->id,
        'enabled' => true,
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/__test/optional-payments-module')
        ->assertForbidden();

    $plan = Plan::query()->create([
        'name' => ['en' => 'Payments Plan'],
        'description' => ['en' => 'Payments plan'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $plan->modules()->sync([$payments->id]);

    app(CurrentTenant::class)->set($tenant);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => now()->addMonth(),
        'pricing_snapshot' => [
            'modules' => [
                ['key' => 'payments', 'settings' => null],
            ],
        ],
    ])->save();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/__test/optional-payments-module')
        ->assertOk();
});

test('completed workspace requires a usable subscription for core operations', function (): void {
    [$owner, $tenant] = moduleWorkspace('Subscription Gate');

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', true);
    app(CurrentTenant::class)->run($tenant, function () use ($tenant, $settings): void {
        $tenant->forceFill(['settings' => $settings])->save();
    });

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();

    $plan = Plan::query()->create([
        'name' => ['en' => 'Core Plan'],
        'description' => ['en' => 'Core plan'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    app(CurrentTenant::class)->set($tenant);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => CarbonImmutable::now()->addMonth(),
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk();
});

test('expired subscription blocks core operations after onboarding completion', function (): void {
    [$owner, $tenant] = moduleWorkspace('Expired Subscription');

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', true);
    app(CurrentTenant::class)->run($tenant, function () use ($tenant, $settings): void {
        $tenant->forceFill(['settings' => $settings])->save();
    });

    $plan = Plan::query()->create([
        'name' => ['en' => 'Expired Plan'],
        'description' => ['en' => 'Expired plan'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    app(CurrentTenant::class)->set($tenant);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now()->subMonth(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => now()->subDay(),
        'status' => SubscriptionStatus::Expired,
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();
});

test('inactive global module blocks tenant access even when tenant module is enabled', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'services')->firstOrFail();
    $module->update(['is_active' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();
});
