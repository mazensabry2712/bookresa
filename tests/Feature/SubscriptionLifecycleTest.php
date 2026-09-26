<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CancelSubscription;
use App\Domain\Billing\Services\ClearPlanChange;
use App\Domain\Billing\Services\ExpireDueSubscriptions;
use App\Domain\Billing\Services\ReactivateSubscription;
use App\Domain\Billing\Services\RenewSubscription;
use App\Domain\Billing\Services\SchedulePlanChange;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function lifecycleTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

function lifecyclePlan(array $overrides = []): Plan
{
    return Plan::query()->create(array_merge([
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter plan'],
        'price_minor' => 19900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ], $overrides));
}

test('active subscription is not usable until its payment is paid', function (): void {
    lifecycleTenant('usable-pending');
    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    expect($subscription->isUsable())->toBeFalse();

    $subscription->forceFill(['payment_status' => PaymentStatus::Paid])->save();
    $subscription->refresh();

    expect($subscription->isUsable())->toBeTrue();
});

test('trial subscription is usable while trial is active', function (): void {
    lifecycleTenant('usable-trial');

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(['trial_days' => 14]),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    expect($subscription->status)->toBe(SubscriptionStatus::Trial)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Pending)
        ->and($subscription->isUsable())->toBeTrue();
});

test('cancellation is scheduled for the current billing boundary', function (): void {
    lifecycleTenant('cancel-subscription');
    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(['trial_days' => 0]),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $result = app(CancelSubscription::class)->handle($subscription);

    expect($result->cancelled_at->equalTo($subscription->end_at))->toBeTrue()
        ->and($result->status)->toBe(SubscriptionStatus::Active)
        ->and($result->metadata['cancel_at_period_end'])->toBeTrue();
});

test('suspended subscription cancellation fails with a domain exception', function (): void {
    lifecycleTenant('cancel-suspended');

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $subscription->forceFill(['status' => SubscriptionStatus::Suspended])->save();

    expect(fn () => app(CancelSubscription::class)->handle($subscription->fresh()))
        ->toThrow(RuntimeException::class);
});

test('scheduled cancellation can be reactivated before the boundary', function (): void {
    lifecycleTenant('reactivate-subscription');
    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    app(CancelSubscription::class)->handle($subscription);
    $reactivated = app(ReactivateSubscription::class)->handle($subscription->fresh());

    expect($reactivated->cancelled_at)->toBeNull()
        ->and($reactivated->metadata['cancel_at_period_end'])->toBeFalse();
});

test('plan change is scheduled at the end of the current period', function (): void {
    lifecycleTenant('schedule-plan');
    $current = lifecyclePlan();
    $next = lifecyclePlan([
        'name' => ['en' => 'Business', 'ar' => 'أعمال'],
        'price_minor' => 39900,
        'included_customer_limit' => 50,
    ]);

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        $current,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $result = app(SchedulePlanChange::class)->handle($subscription, $next);

    expect($result->next_plan_id)->toBe($next->id)
        ->and($result->plan_change_effective_at->equalTo($subscription->end_at))->toBeTrue();
});

test('scheduled plan change can be cleared', function (): void {
    lifecycleTenant('clear-plan');
    $current = lifecyclePlan();
    $next = lifecyclePlan(['price_minor' => 39900]);

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        $current,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    app(SchedulePlanChange::class)->handle($subscription, $next);
    $result = app(ClearPlanChange::class)->handle($subscription->fresh());

    expect($result->next_plan_id)->toBeNull()
        ->and($result->plan_change_effective_at)->toBeNull();
});

test('due subscriptions are expired atomically', function (): void {
    lifecycleTenant('expire-subscription');
    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $count = app(ExpireDueSubscriptions::class)->handle(
        CarbonImmutable::parse('2026-11-01 00:00:00', 'UTC')->addSecond(),
    );

    expect($count)->toBe(1)
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

test('renewal applies the scheduled next plan and resets payment to pending', function (): void {
    lifecycleTenant('renew-subscription');
    $current = lifecyclePlan([
        'price_minor' => 19900,
        'included_customer_limit' => 10,
    ]);
    $next = lifecyclePlan([
        'name' => ['en' => 'Business', 'ar' => 'أعمال'],
        'price_minor' => 39900,
        'included_customer_limit' => 50,
    ]);

    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        $current,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    app(SchedulePlanChange::class)->handle($subscription, $next);
    $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();

    $renewed = app(RenewSubscription::class)->handle(
        $subscription->fresh(),
        CarbonImmutable::parse('2026-11-02 00:00:00', 'UTC'),
    );

    expect($renewed->plan_id)->toBe($next->id)
        ->and($renewed->status)->toBe(SubscriptionStatus::Active)
        ->and($renewed->payment_status)->toBe(PaymentStatus::Pending)
        ->and($renewed->price_minor)->toBe(39900)
        ->and($renewed->included_customer_limit)->toBe(50)
        ->and($renewed->next_plan_id)->toBeNull();
});

test('renewal is blocked while subscription is still active', function (): void {
    lifecycleTenant('renew-blocked');
    $subscription = app(\App\Domain\Billing\Services\CreateSubscription::class)->handle(
        lifecyclePlan(),
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    expect(fn () => app(RenewSubscription::class)->handle(
        $subscription,
        CarbonImmutable::parse('2026-10-15 00:00:00', 'UTC'),
    ))->toThrow(RuntimeException::class);
});


test('subscription expiry artisan command is available', function (): void {
    $this->artisan('subscriptions:expire')->assertExitCode(0);
});
