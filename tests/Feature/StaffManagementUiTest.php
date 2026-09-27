<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
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
use RuntimeException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function staffWorkspaceOwner(string $name = 'Staff Workspace Owner'): array
{
    $user = User::factory()->create([
        'name' => $name,
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Staff Clinic',
        'name_en' => 'Staff Clinic',
        'name_ar' => 'عيادة الموظفين',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.step', 'ready');
    data_set($settings, 'onboarding.completed', true);
    $tenant->forceFill(['settings' => $settings])->save();

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Test Core Plan'],
        'description' => ['en' => 'Test core plan'],
        'price_minor' => 0,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 100,
        'additional_customer_price_minor' => 0,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => CarbonImmutable::now()->addMonth(),
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    return [$user, $tenant];
}

function staffService(Tenant $tenant, string $name): Service
{
    app(CurrentTenant::class)->set($tenant);

    return app(CreateService::class)->handle([
        'name' => ['en' => $name, 'ar' => $name],
        'price_minor' => 10000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'currency' => 'EGP',
    ]);
}

test('owner can add a staff member and assign services through workspace ui', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner();
    $staffUser = User::factory()->create([
        'name' => 'Dr. Staff',
        'email' => 'staff@example.com',
    ]);

    $serviceA = staffService($tenant, 'Consultation');
    $serviceB = staffService($tenant, 'Follow up');

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Staff members');

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->post(route('staff.store', ['tenant' => $tenant->slug]), [
            'email' => $staffUser->email,
            'display_name' => 'Dr. Staff',
            'phone' => '01012345678',
            'job_title' => 'Doctor',
            'role' => 'staff',
            'services' => [$serviceA->id],
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);
    setPermissionsTeamId($tenant->id);

    $staff = StaffProfile::query()->with('services')->where('user_id', $staffUser->id)->firstOrFail();

    expect($staff->display_name)->toBe('Dr. Staff')
        ->and($staff->phone)->toBe('01012345678')
        ->and($staff->services->pluck('id')->all())->toBe([$serviceA->id])
        ->and($staff->user->hasRole('staff'))->toBeTrue();

    unset($serviceB);
});

test('staff list can search and filter by status', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner();
    $activeUser = User::factory()->create(['email' => 'alpha@example.com', 'name' => 'Active Staff']);
    $inactiveUser = User::factory()->create(['email' => 'beta@example.com', 'name' => 'Inactive Staff']);

    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($activeUser, 'staff', ['display_name' => 'Active Staff']);
    $inactive = app(AddStaffMember::class)->handle($inactiveUser, 'staff', ['display_name' => 'Inactive Staff']);
    $inactive->forceFill(['status' => 'inactive'])->save();

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug, 'search' => 'alpha@example.com']))
        ->assertOk()
        ->assertSee('Active Staff')
        ->assertDontSee('Inactive Staff');

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug, 'status' => 'inactive']))
        ->assertOk()
        ->assertSee('Inactive Staff')
        ->assertDontSee('Active Staff');
});

test('manager can update staff role status and service assignments', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner();
    $staffUser = User::factory()->create([
        'email' => 'manager-target@example.com',
    ]);
    $serviceA = staffService($tenant, 'Primary');
    $serviceB = staffService($tenant, 'Secondary');

    app(CurrentTenant::class)->set($tenant);
    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff', [
        'display_name' => 'Initial Staff',
        'job_title' => 'Assistant',
    ]);

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->put(route('staff.update', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'display_name' => 'Updated Staff',
            'phone' => '01111111111',
            'job_title' => 'Senior Assistant',
            'role' => 'manager',
            'status' => 'inactive',
            'services' => [$serviceB->id],
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);
    setPermissionsTeamId($tenant->id);

    $staff = $staff->fresh(['user', 'services']);

    expect($staff->display_name)->toBe('Updated Staff')
        ->and($staff->status->value)->toBe('inactive')
        ->and($staff->services->pluck('id')->all())->toBe([$serviceB->id])
        ->and($staff->user->hasRole('manager'))->toBeTrue()
        ->and(TenantMembership::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $staffUser->id)
            ->value('status'))
        ->toBe(MembershipStatus::Suspended);

    unset($serviceA);
});

test('staff management is tenant isolated', function (): void {
    [$ownerA, $tenantA] = staffWorkspaceOwner('Owner A');
    [$ownerB, $tenantB] = staffWorkspaceOwner('Owner B');
    $staffUserA = User::factory()->create(['email' => 'tenant-a-staff@example.com']);

    app(CurrentTenant::class)->set($tenantA);
    $staffA = app(AddStaffMember::class)->handle($staffUserA, 'staff');

    $this->actingAs($ownerB)->withSession(['tenant_id' => $tenantB->id])
        ->get(route('staff.index', ['tenant' => $tenantB->slug]))
        ->assertOk()
        ->assertDontSee('tenant-a-staff@example.com');

    $this->actingAs($ownerB)->withSession(['tenant_id' => $tenantB->id])
        ->put(route('staff.update', ['tenant' => $tenantB->slug, 'staff' => $staffA]), [
            'display_name' => 'Cross Tenant',
            'phone' => '',
            'job_title' => '',
            'role' => 'staff',
            'status' => 'active',
            'services' => [],
        ])
        ->assertNotFound();

    unset($ownerA);
});

test('receptionist cannot access or manage staff', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner();
    $receptionist = User::factory()->create(['email' => 'receptionist@example.com']);

    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($receptionist, 'receptionist');

    $this->actingAs($receptionist)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertForbidden();

    $this->actingAs($receptionist)->withSession(['tenant_id' => $tenant->id])
        ->post(route('staff.store', ['tenant' => $tenant->slug]), [
            'email' => User::factory()->create()->email,
            'role' => 'staff',
        ])
        ->assertForbidden();

    unset($owner);
});

test('same user can be staff in multiple tenants without moving their profile', function (): void {
    [$ownerA, $tenantA] = staffWorkspaceOwner('Owner A');
    [$ownerB, $tenantB] = staffWorkspaceOwner('Owner B');
    $sharedUser = User::factory()->create(['email' => 'shared@example.com']);

    app(CurrentTenant::class)->set($tenantA);
    $staffA = app(AddStaffMember::class)->handle($sharedUser, 'manager', [
        'display_name' => 'Tenant A Staff',
    ]);

    app(CurrentTenant::class)->set($tenantB);
    $staffB = app(AddStaffMember::class)->handle($sharedUser, 'receptionist', [
        'display_name' => 'Tenant B Staff',
    ]);

    expect($staffA->tenant_id)->toBe($tenantA->id)
        ->and($staffB->tenant_id)->toBe($tenantB->id)
        ->and($staffA->id)->not->toBe($staffB->id)
        ->and(StaffProfile::withoutGlobalScopes()->where('user_id', $sharedUser->id)->count())->toBe(2);

    setPermissionsTeamId($tenantA->id);
    $sharedUser->unsetRelation('roles')->unsetRelation('permissions');
    expect($sharedUser->hasRole('manager'))->toBeTrue()
        ->and($sharedUser->hasRole('receptionist'))->toBeFalse();

    setPermissionsTeamId($tenantB->id);
    $sharedUser->unsetRelation('roles')->unsetRelation('permissions');
    expect($sharedUser->hasRole('receptionist'))->toBeTrue()
        ->and($sharedUser->hasRole('manager'))->toBeFalse();

    unset($ownerA, $ownerB);
});

test('workspace owner cannot be added as staff', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner();

    app(CurrentTenant::class)->set($tenant);

    expect(fn () => app(AddStaffMember::class)->handle($owner, 'staff'))
        ->toThrow(RuntimeException::class);
});

test('staff management paginates large staff lists', function (): void {
    [$owner, $tenant] = staffWorkspaceOwner('Pagination Owner');

    app(CurrentTenant::class)->set($tenant);

    for ($i = 1; $i <= 21; $i++) {
        $user = User::factory()->create([
            'email' => 'pagination-staff-'.$i.'@example.com',
            'name' => 'Pagination Staff '.$i,
        ]);

        app(AddStaffMember::class)->handle($user, 'staff', [
            'display_name' => 'Pagination Staff '.$i,
        ]);
    }

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Pagination Staff 21')
        ->assertDontSee('<p class="text-base font-semibold">Pagination Staff 1</p>', false);

    $this->actingAs($owner)->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index', ['tenant' => $tenant->slug, 'page' => 2]))
        ->assertOk()
        ->assertSee('Pagination Staff 1');
});
