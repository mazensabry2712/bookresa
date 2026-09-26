<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Models\UsagePeriod;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Billing\Services\CreateUsagePeriod;
use App\Domain\Billing\Services\PlanPricingSnapshot;
use App\Domain\Billing\Services\UpsertPlan;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Module\Models\Module;
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

function billingTenant(string $slug): Tenant
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

function billingPlan(array $overrides = []): Plan
{
    return Plan::query()->create(array_merge([
        'name' => ['en' => 'Professional', 'ar' => 'احترافي'],
        'description' => ['en' => 'Professional plan'],
        'price_minor' => 29900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ], $overrides));
}

test('plan pricing can be created with active modules', function (): void {
    $calendar = Module::query()->create([
        'key' => 'calendar',
        'name' => ['en' => 'Calendar'],
        'is_core' => true,
        'is_active' => true,
    ]);

    $result = app(UpsertPlan::class)->handle(null, [
        'name' => ['en' => 'Professional', 'ar' => 'احترافي'],
        'price_minor' => 50000,
        'currency' => 'egp',
        'billing_period' => '3_months',
        'included_customer_limit' => 100,
        'additional_customer_price_minor' => 500,
        'trial_days' => 14,
    ], [$calendar->id]);

    expect($result->price_minor)->toBe(50000)
        ->and($result->currency)->toBe('EGP')
        ->and($result->billing_period)->toBe(PlanBillingPeriod::ThreeMonths)
        ->and($result->modules->pluck('key')->all())->toBe(['calendar']);
});

test('subscription snapshots plan pricing and calculates its billing window', function (): void {
    $tenant = billingTenant('subscription-clinic');
    $plan = billingPlan([
        'billing_period' => PlanBillingPeriod::ThreeMonths,
        'trial_days' => 0,
    ]);

    $start = CarbonImmutable::parse('2026-10-01 10:00:00', 'UTC');
    $subscription = app(CreateSubscription::class)->handle($plan, $start);

    expect($subscription->tenant_id)->toBe($tenant->id)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Pending)
        ->and($subscription->start_at->equalTo($start))->toBeTrue()
        ->and($subscription->end_at->equalTo($start->addMonths(3)))->toBeTrue()
        ->and($subscription->pricing_snapshot['price_minor'])->toBe(29900)
        ->and($subscription->pricing_snapshot['included_customer_limit'])->toBe(20);
});

test('trial subscription uses trial days and keeps payment pending', function (): void {
    billingTenant('trial-clinic');

    $plan = billingPlan(['trial_days' => 14]);
    $start = CarbonImmutable::parse('2026-10-01 10:00:00', 'UTC');

    $subscription = app(CreateSubscription::class)->handle($plan, $start);

    expect($subscription->status)->toBe(SubscriptionStatus::Trial)
        ->and($subscription->payment_status)->toBe(PaymentStatus::Pending)
        ->and($subscription->end_at->equalTo($start->addDays(14)))->toBeTrue();
});

test('tenant cannot have two overlapping active subscriptions', function (): void {
    billingTenant('duplicate-subscription');

    $plan = billingPlan();
    $start = CarbonImmutable::parse('2026-10-01 10:00:00', 'UTC');

    app(CreateSubscription::class)->handle($plan, $start);

    expect(fn () => app(CreateSubscription::class)->handle($plan, $start->addDay()))
        ->toThrow(LogicException::class);
});

test('subscription usage counts unique customers rather than bookings', function (): void {
    $tenant = billingTenant('usage-clinic');
    $plan = billingPlan([
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1500,
        'price_minor' => 29900,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    for ($i = 1; $i <= 27; $i++) {
        Customer::query()->create([
            'name' => 'Customer '.$i,
            'phone' => sprintf('0100000%04d', $i),
            'normalized_phone' => sprintf('20100000%05d', $i),
        ]);
    }

    // Multiple bookings must not change customer usage; the usage source is the customer table.
    $summary = app(CalculateSubscriptionUsage::class)->handle($subscription);

    expect($summary->uniqueCustomerCount)->toBe(27)
        ->and($summary->additionalCustomerCount)->toBe(7)
        ->and($summary->usageChargeMinor)->toBe(10500)
        ->and($summary->totalChargeMinor)->toBe(40400)
        ->and($summary->currency)->toBe('EGP');
});

test('customer limit policy can allow overage or block new customer creation', function (): void {
    billingTenant('customer-limit-policy');

    $plan = billingPlan([
        'included_customer_limit' => 2,
        'additional_customer_price_minor' => 500,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );
    $subscription->forceFill(['payment_status' => PaymentStatus::Paid])->save();

    for ($i = 1; $i <= 2; $i++) {
        Customer::query()->create([
            'name' => 'Customer '.$i,
            'phone' => sprintf('0100000000%d', $i),
            'normalized_phone' => sprintf('20100000000%d', $i),
        ]);
    }

    app(\App\Domain\Business\Actions\UpdateBusinessProfile::class)->handle([
        'name_en' => 'Policy Test',
        'name_ar' => 'اختبار',
        'description_en' => null,
        'description_ar' => null,
        'phone' => null,
        'email' => null,
        'location' => null,
        'address' => null,
        'website' => null,
        'facebook' => null,
        'instagram' => null,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
        'payment_mode' => 'pay_later',
        'deposit_percent' => 50,
        'customer_email_required' => false,
        'customer_limit_policy' => 'block_new_customers',
    ]);

    expect(fn () => app(\App\Domain\Customer\Actions\CreateCustomer::class)->handle([
        'name' => 'Customer 3',
        'phone' => '01000000003',
        'email' => null,
    ]))->toThrow(RuntimeException::class);

    $profile = app(\App\Domain\Business\Models\BusinessProfile::class)->firstOrFail();
    $settings = $profile->booking_settings ?? [];
    data_set($settings, 'customer_limit_policy', 'allow_overage');
    $profile->forceFill(['booking_settings' => $settings])->save();

    $created = app(\App\Domain\Customer\Actions\CreateCustomer::class)->handle([
        'name' => 'Customer 3',
        'phone' => '01000000003',
        'email' => null,
    ]);

    expect($created->name)->toBe('Customer 3');
});

test('usage period is immutable and idempotent for the same subscription window', function (): void {
    billingTenant('usage-period-clinic');
    $plan = billingPlan([
        'included_customer_limit' => 2,
        'additional_customer_price_minor' => 1000,
        'price_minor' => 29900,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    Customer::query()->create([
        'name' => 'One',
        'phone' => '01000000001',
        'normalized_phone' => '201000000001',
    ]);
    Customer::query()->create([
        'name' => 'Two',
        'phone' => '01000000002',
        'normalized_phone' => '201000000002',
    ]);
    Customer::query()->create([
        'name' => 'Three',
        'phone' => '01000000003',
        'normalized_phone' => '201000000003',
    ]);

    $first = app(CreateUsagePeriod::class)->handle($subscription);
    $second = app(CreateUsagePeriod::class)->handle($subscription);

    expect($second->id)->toBe($first->id)
        ->and(UsagePeriod::query()->count())->toBe(1)
        ->and($first->unique_customer_count)->toBe(3)
        ->and($first->additional_customer_count)->toBe(1)
        ->and($first->usage_charge_minor)->toBe(1000)
        ->and($first->total_charge_minor)->toBe(30900)
        ->and($first->charges)->toHaveCount(1);
});

test('historical pricing snapshot does not change when the plan is edited', function (): void {
    billingTenant('pricing-snapshot-clinic');

    $plan = billingPlan([
        'price_minor' => 29900,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 1000,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $plan->update([
        'price_minor' => 59900,
        'included_customer_limit' => 50,
        'additional_customer_price_minor' => 2000,
    ]);

    $subscription->refresh();

    expect($subscription->price_minor)->toBe(29900)
        ->and($subscription->included_customer_limit)->toBe(20)
        ->and($subscription->additional_customer_price_minor)->toBe(1000)
        ->and($subscription->pricing_snapshot['price_minor'])->toBe(29900)
        ->and($subscription->pricing_snapshot['included_customer_limit'])->toBe(20);
});

test('usage cannot be calculated through another tenant context', function (): void {
    $tenantA = billingTenant('usage-a');
    $plan = billingPlan();

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::parse('2026-10-01 00:00:00', 'UTC'),
    );

    $tenantB = billingTenant('usage-b');
    app(CurrentTenant::class)->set($tenantB);

    expect(fn () => app(CalculateSubscriptionUsage::class)->handle($subscription))
        ->toThrow(LogicException::class);

    expect($tenantA->id)->not->toBe($tenantB->id);
});
