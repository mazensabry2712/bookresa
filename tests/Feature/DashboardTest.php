<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
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

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function dashboardWorkspace(): array
{
    $user = User::factory()->create([
        'name' => 'Dashboard Owner',
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Dashboard Clinic',
        'name_en' => 'Dashboard Clinic',
        'name_ar' => 'عيادة لوحة التحكم',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', true);
    data_set($settings, 'onboarding.step', 'ready');
    $tenant->forceFill(['settings' => $settings])->save();

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Dashboard Plan', 'ar' => 'خطة لوحة التحكم'],
        'description' => ['en' => 'Dashboard test plan', 'ar' => 'خطة اختبار لوحة التحكم'],
        'price_minor' => 29900,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 20,
        'additional_customer_price_minor' => 500,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => CarbonImmutable::now('UTC')->addMonth(),
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    return [$user, $tenant];
}

function dashboardCustomer(Tenant $tenant, string $name, string $phone): Customer
{
    app(CurrentTenant::class)->set($tenant);

    return Customer::query()->create([
        'tenant_id' => $tenant->id,
        'name' => $name,
        'phone' => $phone,
        'normalized_phone' => preg_replace('/\D+/', '', $phone),
        'first_seen_at' => CarbonImmutable::now('UTC'),
        'last_seen_at' => CarbonImmutable::now('UTC'),
    ]);
}

function dashboardBooking(Tenant $tenant, Customer $customer, string $reference, string $status): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'currency' => 'EGP',
    ]);

    $start = CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo');
    $end = $start->addMinutes(30);

    return Booking::query()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => $start->utc(),
        'ends_at' => $end->utc(),
        'block_ends_at' => $end->utc(),
        'status' => $status,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

test('dashboard shows business identity and core operating metrics', function (): void {
    [$user, $tenant] = dashboardWorkspace();

    dashboardCustomer($tenant, 'Customer One', '01000000111');
    dashboardCustomer($tenant, 'Customer Two', '01000000222');
    dashboardBooking($tenant, Customer::query()->where('name', 'Customer One')->firstOrFail(), 'BR-DASH-001', BookingStatus::Confirmed->value);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Dashboard Clinic')
        ->assertSee('logo.png', false)
        ->assertSee('logodark.png', false)
        ->assertSee('data-bookresa-sidebar-collapse', false)
        ->assertSee('data-workspace-switcher', false)
        ->assertSee(__('app.operations'))
        ->assertSee(__('app.finance'))
        ->assertSee(__('app.insights'))
        ->assertSee(__('app.customers'))
        ->assertSee('Customer One')
        ->assertSee('BR-DASH-001')
        ->assertSee('Dashboard Plan')
        ->assertSee('10%');
});

test('dashboard excludes terminal bookings from upcoming list', function (): void {
    [$user, $tenant] = dashboardWorkspace();
    $customer = dashboardCustomer($tenant, 'Terminal Customer', '01000000333');

    $upcoming = dashboardBooking($tenant, $customer, 'BR-DASH-UPCOMING', BookingStatus::Confirmed->value);
    $completed = dashboardBooking($tenant, $customer, 'BR-DASH-COMPLETED', BookingStatus::Completed->value);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee($upcoming->booking_reference)
        ->assertDontSee($completed->booking_reference);
});

test('dashboard remains accessible during incomplete onboarding', function (): void {
    [$user, $tenant] = dashboardWorkspace();

    $settings = $tenant->fresh()->settings ?? [];
    data_set($settings, 'onboarding.completed', false);
    data_set($settings, 'onboarding.step', 'services');
    $tenant->forceFill(['settings' => $settings])->save();

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee(__('app.your_setup'))
        ->assertSee(__('app.onboarding_steps.services'))
        ->assertSee(route('services.index', ['tenant' => $tenant->slug]), false);
});

test('staff dashboard is scoped to assigned operations and hides billing metrics', function (): void {
    [, $tenant] = dashboardWorkspace();

    $staffUser = User::factory()->create([
        'name' => 'Dashboard Staff',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $staffRole = app(TenantRoleProvisioner::class)->provisionRole($tenant, 'staff');

    setPermissionsTeamId($tenant->id);
    $staffUser->assignRole($staffRole);

    app(CurrentTenant::class)->set($tenant);

    $staffProfile = StaffProfile::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'display_name' => 'Dashboard Staff',
        'status' => StaffStatus::Active,
    ]);

    $assignedCustomer = dashboardCustomer($tenant, 'Assigned Customer', '01000000444');
    $otherCustomer = dashboardCustomer($tenant, 'Other Customer', '01000000555');

    $assignedBooking = dashboardBooking(
        $tenant,
        $assignedCustomer,
        'BR-DASH-STAFF-001',
        BookingStatus::Confirmed->value,
    );
    $assignedBooking->update(['staff_id' => $staffProfile->id]);

    dashboardBooking(
        $tenant,
        $otherCustomer,
        'BR-DASH-STAFF-002',
        BookingStatus::Confirmed->value,
    );

    $this->actingAs($staffUser)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee($assignedBooking->booking_reference)
        ->assertDontSee('BR-DASH-STAFF-002')
        ->assertSee('data-dashboard-metric="customers" data-metric-value="1"', false)
        ->assertSee('data-dashboard-metric="today-revenue" data-metric-value="—"', false)
        ->assertDontSee(__('app.dashboard_ui.customer_usage'));
});

test('staff account without a profile cannot open the dashboard', function (): void {
    [$owner, $tenant] = dashboardWorkspace();

    $staffUser = User::factory()->create([
        'name' => 'Unprovisioned Staff',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $staffUser->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $staffRole = app(TenantRoleProvisioner::class)->provisionRole($tenant, 'staff');

    setPermissionsTeamId($tenant->id);
    $staffUser->assignRole($staffRole);

    $this->actingAs($staffUser)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertForbidden();
});


test('dashboard canonical URL contains the tenant slug and legacy dashboard redirects to it', function (): void {
    [$user, $tenant] = dashboardWorkspace();

    $canonical = route('dashboard', ['tenant' => $tenant->slug]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/dashboard')
        ->assertRedirect($canonical);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get($canonical)
        ->assertOk();
});

test('workspace dashboard rejects a tenant the authenticated user does not belong to', function (): void {
    [$user, $tenant] = dashboardWorkspace();

    $otherTenant = Tenant::query()->create([
        'slug' => 'other-dashboard-workspace',
        'status' => \App\Domain\Tenant\Enums\TenantStatus::Active,
        'settings' => [
            'onboarding' => [
                'completed' => true,
                'step' => 'ready',
            ],
        ],
    ]);

    expect($otherTenant->id)->not->toBe($tenant->id);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('dashboard', ['tenant' => $otherTenant->slug]))
        ->assertForbidden();
});


test('legacy dashboard subpaths redirect to the tenant canonical workspace path', function (): void {
    [$user, $tenant] = dashboardWorkspace();

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/dashboard/bookings')
        ->assertRedirect(route('booking.management.index', ['tenant' => $tenant->slug]));
});
