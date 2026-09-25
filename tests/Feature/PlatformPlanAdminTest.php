<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Module\Models\Module;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function platformAdminUser(bool $admin = true): User
{
    $user = User::factory()->create();
    if ($admin) {
        PlatformAdmin::query()->create(['user_id' => $user->id, 'is_active' => true]);
    }
    return $user;
}

test('non platform admin cannot access plan management', function (): void {
    $user = platformAdminUser(false);
    $this->actingAs($user)->get(route('admin.plans.index'))->assertForbidden();
});

test('platform admin can create and edit a plan with active modules', function (): void {
    $user = platformAdminUser();
    $module = Module::query()->create([
        'key' => 'appointments',
        'name' => ['en' => 'Appointments', 'ar' => 'المواعيد'],
        'description' => ['en' => 'Appointments'],
        'is_core' => true,
        'is_active' => true,
    ]);
    $inactive = Module::query()->create([
        'key' => 'future',
        'name' => ['en' => 'Future', 'ar' => 'مستقبل'],
        'description' => ['en' => 'Future'],
        'is_core' => false,
        'is_active' => false,
    ]);

    $this->actingAs($user)->post(route('admin.plans.store'), [
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter', 'ar' => ''],
        'price' => '199.00',
        'currency' => 'egp',
        'billing_period' => 'monthly',
        'included_customer_limit' => 20,
        'additional_customer_price' => '10.50',
        'trial_days' => 14,
        'is_active' => 1,
        'module_ids' => [$module->id, $inactive->id],
    ])->assertRedirect();

    $plan = Plan::query()->latest('id')->firstOrFail();
    expect($plan->price_minor)->toBe(19900)
        ->and($plan->additional_customer_price_minor)->toBe(1050)
        ->and($plan->currency)->toBe('EGP')
        ->and($plan->modules->pluck('id')->all())->toBe([$module->id]);

    $this->actingAs($user)->patch(route('admin.plans.toggle', $plan))->assertSessionHas('status');
    expect($plan->fresh()->is_active)->toBeFalse();
});

test('platform admin can view plan listing', function (): void {
    $user = platformAdminUser();
    Plan::query()->create([
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter'],
        'price_minor' => 19900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($user)->get(route('admin.plans.index'))->assertOk()->assertSee('Starter');
});

test('inactive platform admin is denied', function (): void {
    $user = User::factory()->create();
    PlatformAdmin::query()->create(['user_id' => $user->id, 'is_active' => false]);
    $this->actingAs($user)->get(route('admin.plans.index'))->assertForbidden();
});
