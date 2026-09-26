<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Domain\Tenant\Models\Tenant;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('web responses include baseline security headers', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('audit logger records sensitive application events without secrets', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    app(AuditLogger::class)->log(
        'security.test_event',
        $user,
        [
            'action' => 'test',
            'tenant_id' => 123,
        ],
    );

    $activity = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'security.test_event')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($user->id)
        ->and($activity->properties['tenant_id'] ?? null)->toBe(123)
        ->and($activity->properties['password'] ?? null)->toBeNull()
        ->and($activity->properties['api_key'] ?? null)->toBeNull();
});


test('staff role changes are audited', function (): void {
    $this->seed([ModuleSeeder::class, BusinessTypeSeeder::class]);

    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'audit-staff@example.com']);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Audit Clinic',
        'name_en' => 'Audit Clinic',
        'name_ar' => 'عيادة التدقيق',
    ]);

    $this->actingAs($owner);
    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($staffUser, 'staff');

    $activity = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'staff.role_assigned')
        ->latest('id')
        ->firstOrFail();

    expect($activity->causer_id)->toBe($owner->id)
        ->and($activity->properties['tenant_id'] ?? null)->toBe($tenant->id)
        ->and($activity->properties['user_id'] ?? null)->toBe($staffUser->id)
        ->and($activity->properties['role'] ?? null)->toBe('staff');
});

test('payment status changes are audited', function (): void {
    $tenant = Tenant::query()->create(['slug' => 'audit-payment']);
    app(CurrentTenant::class)->set($tenant);

    $payment = Payment::query()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => User::class,
        'payable_id' => User::factory()->create()->id,
        'reference' => 'PAY-AUDIT-TEST',
        'provider' => 'test',
        'amount_minor' => 10000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Pending,
    ]);

    app(PaymentService::class)->applyResult(
        $payment,
        new PaymentGatewayResult(
            status: PaymentStatus::Paid,
            providerReference: 'provider-audit-1',
        ),
    );

    $activity = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'payment.status_changed')
        ->latest('id')
        ->firstOrFail();

    expect($activity->properties['tenant_id'] ?? null)->toBe($tenant->id)
        ->and($activity->properties['payment_id'] ?? null)->toBe($payment->id)
        ->and($activity->properties['from_status'] ?? null)->toBe('pending')
        ->and($activity->properties['to_status'] ?? null)->toBe('paid');
});

test('platform pricing changes are audited', function (): void {
    $admin = User::factory()->create();
    \App\Domain\Platform\Models\PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.plans.store'), [
        'name' => ['en' => 'Audit Starter', 'ar' => 'البداية'],
        'description' => ['en' => 'Audit', 'ar' => 'تدقيق'],
        'price' => '199.00',
        'currency' => 'egp',
        'billing_period' => PlanBillingPeriod::Monthly->value,
        'included_customer_limit' => 10,
        'additional_customer_price' => '5.00',
        'trial_days' => 7,
        'is_active' => 1,
        'module_ids' => [],
    ])->assertRedirect();

    $plan = Plan::query()->latest('id')->firstOrFail();

    $activity = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'pricing.plan_created')
        ->latest('id')
        ->firstOrFail();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['plan_id'] ?? null)->toBe($plan->id)
        ->and($activity->properties['price_minor'] ?? null)->toBe(19900);
});


test('platform administrator grants and revocations are audited', function (): void {
    $user = User::factory()->create(['email' => 'platform-audit@example.com']);

    $this->artisan('platform-admin:set', ['email' => $user->email])
        ->assertExitCode(0);

    $granted = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'platform_admin.granted')
        ->latest('id')
        ->firstOrFail();

    expect($granted->properties['user_id'] ?? null)->toBe($user->id)
        ->and($granted->properties['is_active'] ?? null)->toBeTrue();

    $this->artisan('platform-admin:set', ['email' => $user->email, '--revoke' => true])
        ->assertExitCode(0);

    $revoked = Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'platform_admin.revoked')
        ->latest('id')
        ->firstOrFail();

    expect($revoked->properties['user_id'] ?? null)->toBe($user->id)
        ->and($revoked->properties['is_active'] ?? null)->toBeFalse();
});
