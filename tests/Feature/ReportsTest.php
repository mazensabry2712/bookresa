<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Service\Actions\CreateService;
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

function reportTenant(string $slug): Tenant
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

function reportUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    setPermissionsTeamId($tenant->id);

    $permission = Permission::firstOrCreate([
        'name' => 'reports.view',
        'guard_name' => 'web',
    ]);

    $role = Role::firstOrCreate([
        'name' => 'reports-owner',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);

    $role->syncPermissions([$permission]);
    $user->assignRole($role);

    return $user;
}

function reportBooking(Tenant $tenant, string $reference, string $localStart, BookingStatus $status): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $start = CarbonImmutable::parse($localStart, 'Africa/Cairo');

    return Booking::query()->create([
        'customer_id' => Customer::query()->create([
            'name' => 'Customer '.$reference,
            'phone' => '010'.str_pad((string) abs(crc32($reference)), 8, '0', STR_PAD_LEFT),
            'normalized_phone' => '201'.str_pad((string) abs(crc32($reference)), 9, '0', STR_PAD_LEFT),
        ])->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => $start->utc(),
        'ends_at' => $start->addMinutes(30)->utc(),
        'block_ends_at' => $start->addMinutes(30)->utc(),
        'status' => $status,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

function reportPayment(Tenant $tenant, Booking $booking, int $amountMinor, string $reference): Payment
{
    app(CurrentTenant::class)->set($tenant);

    return Payment::query()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => $reference,
        'provider' => 'kashier',
        'amount_minor' => $amountMinor,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'method' => 'card',
        'idempotency_key' => $reference,
        'paid_at' => CarbonImmutable::parse('2026-09-28 12:00', 'Africa/Cairo')->utc(),
    ]);
}

test('business report is permission protected and tenant isolated', function (): void {
    $tenantA = reportTenant('report-clinic-a');
    $userA = reportUser($tenantA, 'report-a@example.com');

    $bookingA = reportBooking($tenantA, 'BR-REPORT-A', '2026-09-28 10:00', BookingStatus::Completed);
    reportBooking($tenantA, 'BR-REPORT-B', '2026-09-28 11:00', BookingStatus::NoShow);
    reportBooking($tenantA, 'BR-REPORT-OUT', '2026-10-05 11:00', BookingStatus::Confirmed);
    reportPayment($tenantA, $bookingA, 25000, 'PAY-REPORT-A');

    $tenantB = reportTenant('report-clinic-b');
    reportBooking($tenantB, 'BR-REPORT-FOREIGN', '2026-09-28 10:00', BookingStatus::Completed);

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('reports.business', ['from' => '2026-09-28', 'to' => '2026-09-28']))
        ->assertOk()
        ->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['bookings'] === 2
                && $metrics['customers'] === 2
                && $metrics['revenueMinor'] === 25000
                && $metrics['cancellations'] === 0
                && $metrics['noShows'] === 1
                && $metrics['completed'] === 1;
        })
        ->assertDontSee('BR-REPORT-FOREIGN');
});

test('user without reports permission is forbidden', function (): void {
    $tenant = reportTenant('report-forbidden');
    $user = User::factory()->create(['email' => 'report-forbidden@example.com']);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('reports.business'))
        ->assertForbidden();
});

test('platform report aggregates all tenants independently', function (): void {
    $admin = User::factory()->create(['email' => 'platform-report-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $tenantA = reportTenant('platform-report-a');
    $bookingA = reportBooking($tenantA, 'BR-PLATFORM-A', '2026-09-28 10:00', BookingStatus::Completed);
    reportPayment($tenantA, $bookingA, 100000, 'PAY-PLATFORM-A');

    app(CurrentTenant::class)->clear();

    $plan = Plan::query()->create([
        'name' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'description' => ['en' => 'Starter', 'ar' => 'المبتدئ'],
        'price_minor' => 99900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 500,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    app(CurrentTenant::class)->set($tenantA);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenantA->id,
        'plan_id' => $plan->id,
        'start_at' => CarbonImmutable::parse('2026-09-28', 'UTC'),
        'end_at' => CarbonImmutable::parse('2026-10-28', 'UTC'),
        'status' => SubscriptionStatus::Active,
        'payment_status' => PaymentStatus::Paid,
        'price_minor' => 99900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 500,
        'pricing_snapshot' => ['price_minor' => 99900],
    ]);

    Payment::query()->create([
        'tenant_id' => $tenantA->id,
        'payable_type' => $subscription->getMorphClass(),
        'payable_id' => $subscription->id,
        'reference' => 'PAY-PLATFORM-SUB',
        'provider' => 'kashier',
        'amount_minor' => 99900,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'idempotency_key' => 'platform-sub-payment',
        'paid_at' => CarbonImmutable::parse('2026-09-28 13:00', 'UTC'),
    ]);

    $tenantB = reportTenant('platform-report-b');
    reportBooking($tenantB, 'BR-PLATFORM-B', '2026-09-28 11:00', BookingStatus::Cancelled);

    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['from' => '2026-09-28', 'to' => '2026-09-28']))
        ->assertOk()
        ->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['businesses'] === 2
                && $metrics['bookings'] === 2
                && $metrics['subscriptionsStarted'] === 1
                && $metrics['subscriptionRevenueMinor'] === 99900
                && $metrics['platformRevenueMinor'] === 99900;
        });
});

test('non platform admin cannot access platform reports', function (): void {
    $user = User::factory()->create(['email' => 'not-platform-report@example.com']);

    $this->actingAs($user)
        ->get(route('admin.reports.index'))
        ->assertForbidden();
});
