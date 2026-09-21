<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\StartSubscriptionPayment;
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

function paymentTenant(string $slug, string $email = 'owner@example.com'): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => 'Africa/Cairo',
        'email' => $email,
    ]);

    return $tenant;
}

function paymentUser(Tenant $tenant, string $email): User
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
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $role = Role::firstOrCreate([
        'name' => 'billing-owner',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);

    $role->syncPermissions(['billing.view', 'subscription.manage']);
    $user->assignRole($role);

    return $user;
}

function paymentPlan(array $overrides = []): Plan
{
    return Plan::query()->create(array_merge([
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter'],
        'price_minor' => 19900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ], $overrides));
}

final class FakeSubscriptionGateway implements PaymentGateway
{
    public int $createCalls = 0;

    public function createPayment(PaymentRequest $request): PaymentGatewayResult
    {
        $this->createCalls++;

        return new PaymentGatewayResult(
            status: PaymentStatus::Processing,
            providerReference: 'KASHIER-SESSION-123',
            checkoutUrl: 'https://payments.example.test/session/123',
            metadata: ['test_context' => 'subscription'],
        );
    }

    public function verifyPayment(string $providerReference): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Paid,
            providerReference: $providerReference,
        );
    }

    public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: PaymentStatus::Refunded,
            providerReference: $providerReference,
        );
    }
}

test('subscription checkout creates a tenant-owned payment and returns checkout url', function (): void {
    $tenant = paymentTenant('subscription-pay');
    $plan = paymentPlan();
    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $payment = app(StartSubscriptionPayment::class)->handle($subscription);

    expect($payment->payable_type)->toBe($subscription->getMorphClass())
        ->and($payment->payable_id)->toBe($subscription->id)
        ->and($payment->amount_minor)->toBe(19900)
        ->and($payment->currency)->toBe('EGP')
        ->and($payment->status)->toBe(PaymentStatus::Processing)
        ->and($payment->checkout_url)->toBe('https://payments.example.test/session/123')
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

test('subscription checkout is idempotent for repeated attempts', function (): void {
    $tenant = paymentTenant('subscription-idempotent');
    $plan = paymentPlan();
    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $first = app(StartSubscriptionPayment::class)->handle($subscription);
    $second = app(StartSubscriptionPayment::class)->handle($subscription->fresh());

    expect($second->id)->toBe($first->id)
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

test('trial subscriptions cannot start a paid checkout', function (): void {
    paymentTenant('subscription-trial');
    $subscription = app(CreateSubscription::class)->handle(
        paymentPlan(['trial_days' => 14]),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    expect(fn () => app(StartSubscriptionPayment::class)->handle($subscription))
        ->toThrow(RuntimeException::class);
});

test('subscription billing page requires billing permission', function (): void {
    $tenant = paymentTenant('subscription-forbidden');
    $user = User::factory()->create(['email' => 'forbidden@example.com']);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertForbidden();
});

test('authorized owner can open subscription billing and start checkout', function (): void {
    $tenant = paymentTenant('subscription-dashboard');
    $user = paymentUser($tenant, 'dashboard@example.com');
    $subscription = app(CreateSubscription::class)->handle(
        paymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('billing.subscription'))
        ->assertOk()
        ->assertSee('Starter');

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('billing.subscription.checkout', $subscription))
        ->assertRedirect('https://payments.example.test/session/123');
});

test('subscription checkout route cannot access another tenant subscription', function (): void {
    $tenantA = paymentTenant('subscription-owner-a');
    $userA = paymentUser($tenantA, 'owner-a@example.com');
    $subscriptionA = app(CreateSubscription::class)->handle(
        paymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $tenantB = paymentTenant('subscription-owner-b');
    $subscriptionB = app(CreateSubscription::class)->handle(
        paymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->post(route('billing.subscription.checkout', $subscriptionB))
        ->assertNotFound();

    expect($subscriptionA->exists)->toBeTrue();
});
