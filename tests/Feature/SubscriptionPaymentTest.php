<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Billing\Services\RenewSubscription;
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

function subscriptionPaymentTenant(string $slug, string $email = 'owner@example.com'): Tenant
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

function subscriptionPaymentUser(Tenant $tenant, string $email): User
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

function subscriptionPaymentPlan(array $overrides = []): Plan
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
            providerReference: 'KASHIER-SESSION-'.str_pad((string) $this->createCalls, 3, '0', STR_PAD_LEFT),
            checkoutUrl: 'https://payments.example.test/session/'.str_pad((string) $this->createCalls, 3, '0', STR_PAD_LEFT),
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
    $tenant = subscriptionPaymentTenant('subscription-pay');
    $plan = subscriptionPaymentPlan();
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
        ->and($payment->checkout_url)->toBe('https://payments.example.test/session/001')
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

test('subscription checkout is idempotent for repeated attempts', function (): void {
    $tenant = subscriptionPaymentTenant('subscription-idempotent');
    $plan = subscriptionPaymentPlan();
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
    subscriptionPaymentTenant('subscription-trial');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(['trial_days' => 14]),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    expect(fn () => app(StartSubscriptionPayment::class)->handle($subscription))
        ->toThrow(RuntimeException::class);
});

test('subscription billing page requires billing permission', function (): void {
    $tenant = subscriptionPaymentTenant('subscription-forbidden');
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
    $tenant = subscriptionPaymentTenant('subscription-dashboard');
    $user = subscriptionPaymentUser($tenant, 'dashboard@example.com');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
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
        ->assertRedirect('https://payments.example.test/session/001');
});

test('subscription checkout route cannot access another tenant subscription', function (): void {
    $tenantA = subscriptionPaymentTenant('subscription-owner-a');
    $userA = subscriptionPaymentUser($tenantA, 'owner-a@example.com');
    $subscriptionA = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $tenantB = subscriptionPaymentTenant('subscription-owner-b');
    $subscriptionB = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
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


test('paid subscription payment unlocks subscription entitlement', function (): void {
    $tenant = subscriptionPaymentTenant('subscription-sync-paid');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $payment = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-SYNC-001',
        'provider' => 'kashier',
        'provider_reference' => 'session-sync-001',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
    ]);

    app(\App\Domain\Payment\Services\PaymentService::class)->applyResult(
        $payment,
        new PaymentGatewayResult(
            status: PaymentStatus::Paid,
            providerReference: 'session-sync-001',
        ),
    );

    app(\App\Domain\Payment\Services\SyncSubscriptionPaymentStatus::class)
        ->handle($payment->fresh(), PaymentStatus::Paid);

    $subscription->refresh();

    expect($subscription->payment_status)->toBe(PaymentStatus::Paid)
        ->and($subscription->isUsable())->toBeTrue();
});

test('signed Kashier return completes subscription payment and returns to billing', function (): void {
    $tenant = subscriptionPaymentTenant('subscription-return');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $payment = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-RETURN-001',
        'provider' => 'kashier',
        'provider_reference' => 'session-sub-return-001',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
    ]);

    config([
        'bookresa.payments.kashier.api_key' => 'api-key',
        'bookresa.payments.kashier.secret_key' => 'secret-key',
        'bookresa.payments.kashier.base_url' => 'https://test-api.kashier.io',
    ]);

    Http::fake([
        'https://test-api.kashier.io/v3/payment/sessions/session-sub-return-001/payment' => Http::response([
            'data' => [
                'sessionId' => 'session-sub-return-001',
                'status' => 'PAID',
                'method' => 'card',
                'updatedAt' => '2026-09-21T01:00:00Z',
                'merchantOrderId' => $payment->reference,
            ],
        ]),
    ]);

    $query = [
        'paymentStatus' => 'SUCCESS',
        'cardDataToken' => 'token',
        'maskedCard' => '512345******2346',
        'merchantOrderId' => $payment->reference,
        'orderId' => 'order-sub-return-001',
        'cardBrand' => 'Mastercard',
        'orderReference' => $payment->reference,
        'transactionId' => 'TX-SUB-RETURN-001',
        'amount' => '199',
        'currency' => 'EGP',
        'mode' => 'test',
    ];

    $query['signature'] = app(\App\Infrastructure\Payments\Kashier\KashierRedirectVerifier::class)
        ->sign($query, 'api-key');

    app(CurrentTenant::class)->clear();

    $this->get(route('payments.kashier.return').'?'.http_build_query($query))
        ->assertRedirect(route('billing.subscription'));

    $subscription = Subscription::withoutGlobalScopes()->findOrFail($subscription->id);
    $payment = Payment::withoutGlobalScopes()->findOrFail($payment->id);

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Paid)
        ->and($subscription->isUsable())->toBeTrue();
});

test('signed Kashier webhook completes a subscription payment idempotently', function (): void {
    $tenant = subscriptionPaymentTenant('subscription-webhook');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $payment = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-WEBHOOK-001',
        'provider' => 'kashier',
        'provider_reference' => 'session-sub-webhook-001',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
    ]);

    config(['bookresa.payments.kashier.api_key' => 'api-key']);

    $data = [
        'signatureKeys' => ['amount', 'currency', 'orderReference', 'status', 'transactionId'],
        'amount' => '199',
        'currency' => 'EGP',
        'orderReference' => $payment->reference,
        'status' => 'SUCCESS',
        'transactionId' => 'TX-SUB-WEBHOOK-001',
        'creationDate' => '2026-09-21T01:00:00Z',
        'method' => 'card',
    ];

    $signature = app(\App\Infrastructure\Payments\Kashier\KashierWebhookVerifier::class)
        ->sign($data, 'api-key');

    app(CurrentTenant::class)->clear();

    $payload = [
        'event' => 'pay',
        'data' => $data,
    ];

    $this->postJson(route('webhooks.kashier'), $payload, [
        'x-kashier-signature' => $signature,
    ])->assertNoContent();

    $this->postJson(route('webhooks.kashier'), $payload, [
        'x-kashier-signature' => $signature,
    ])->assertStatus(409);

    $subscription = Subscription::withoutGlobalScopes()->findOrFail($subscription->id);
    $payment = Payment::withoutGlobalScopes()->findOrFail($payment->id);

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Paid)
        ->and($subscription->isUsable())->toBeTrue();
});


test('renewed subscription does not reuse a paid payment from the previous cycle', function (): void {
    subscriptionPaymentTenant('subscription-cycle-isolation');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $oldStart = $subscription->start_at->toIso8601String();

    $oldPayment = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-OLD-CYCLE',
        'provider' => 'kashier',
        'provider_reference' => 'session-old-cycle',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'paid_at' => CarbonImmutable::parse('2026-10-01 01:00:00', 'UTC'),
        'metadata' => [
            'subscription_start' => $oldStart,
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
        ],
    ]);

    $subscription->forceFill([
        'status' => \App\Domain\Billing\Enums\SubscriptionStatus::Expired,
        'payment_status' => PaymentStatus::Paid,
    ])->save();

    $renewed = app(RenewSubscription::class)->handle(
        $subscription->fresh(),
        CarbonImmutable::parse('2026-11-01 00:00:00', 'UTC'),
    );

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $payment = app(StartSubscriptionPayment::class)->handle($renewed);

    expect($payment->id)->not->toBe($oldPayment->id)
        ->and($payment->status)->toBe(PaymentStatus::Processing)
        ->and($payment->metadata['subscription_start'])->toBe($renewed->start_at->toIso8601String())
        ->and($payment->metadata['subscription_id'])->toBe($renewed->id)
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(2);
});

test('expired subscription payment session creates a fresh attempt', function (): void {
    subscriptionPaymentTenant('subscription-expired-session');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $expired = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-EXPIRED-001',
        'provider' => 'kashier',
        'provider_reference' => 'expired-session',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Processing,
        'checkout_url' => 'https://payments.example.test/expired',
        'expires_at' => CarbonImmutable::now('UTC')->subMinute(),
        'idempotency_key' => 'subscription-'.$subscription->id.'-kashier-attempt-1',
    ]);

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $fresh = app(StartSubscriptionPayment::class)->handle($subscription->fresh());

    expect($fresh->id)->not->toBe($expired->id)
        ->and($fresh->status)->toBe(PaymentStatus::Processing)
        ->and($fresh->idempotency_key)->toBe('subscription-'.$subscription->id.'-kashier-attempt-2')
        ->and($fresh->checkout_url)->toBe('https://payments.example.test/session/001')
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(2);
});

test('failed subscription payment can start a fresh payment attempt', function (): void {
    subscriptionPaymentTenant('subscription-retry');
    $subscription = app(CreateSubscription::class)->handle(
        subscriptionPaymentPlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $failedPayment = Payment::query()->create([
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-SUB-RETRY-OLD',
        'provider' => 'kashier',
        'provider_reference' => 'session-old',
        'amount_minor' => 19900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Failed,
        'idempotency_key' => 'subscription-'.$subscription->id.'-kashier-attempt-1',
    ]);

    $gateway = new FakeSubscriptionGateway();
    $this->app->instance(PaymentGateway::class, $gateway);

    $payment = app(StartSubscriptionPayment::class)->handle($subscription->fresh());

    expect($payment->id)->not->toBe($failedPayment->id)
        ->and($payment->status)->toBe(PaymentStatus::Processing)
        ->and($payment->idempotency_key)->toBe('subscription-'.$subscription->id.'-kashier-attempt-2')
        ->and($payment->checkout_url)->toBe('https://payments.example.test/session/001')
        ->and($gateway->createCalls)->toBe(1)
        ->and(Payment::query()->count())->toBe(2);
});
