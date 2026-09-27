<?php

use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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

test('authenticated owner can create a business workspace', function (): void {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $response = $this->actingAs($user)->post('/onboarding/business', [
        'name' => 'Ahmed Clinic',
        'business_type_id' => $type->id,
        'timezone' => 'Africa/Cairo',
        'locale' => 'ar',
    ]);

    $tenant = Tenant::query()->where('slug', 'ahmed-clinic')->firstOrFail();

    $response->assertRedirectToRoute('onboarding.workspace');

    app(CurrentTenant::class)->set($tenant);

    expect($tenant->profile)->not->toBeNull()
        ->and($tenant->business_type_id)->toBe($type->id)
        ->and($user->tenantMemberships()->where('tenant_id', $tenant->id)->where('status', MembershipStatus::Active)->exists())->toBeTrue()
        ->and($tenant->modules()->wherePivot('enabled', true)->count())->toBe(6);

    setPermissionsTeamId($tenant->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    expect($user->hasRole('owner'))->toBeTrue();

    $ownerRole = Role::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'owner')
        ->firstOrFail();

    expect($ownerRole->permissions->pluck('name')->contains('bookings.create'))->toBeTrue()
        ->and($user->roles()->whereKey($ownerRole->getKey())->exists())->toBeTrue();
});

test('business onboarding rejects inactive business types', function (): void {
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();
    $type->update(['is_active' => false]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/onboarding/business', [
            'name' => 'Inactive Type Business',
            'business_type_id' => $type->id,
        ])
        ->assertSessionHasErrors('business_type_id');

    expect(Tenant::query()->count())->toBe(0);
});

test('business profile cannot be updated across tenant context', function (): void {
    $tenantA = Tenant::query()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::query()->create(['slug' => 'tenant-b']);

    $profileA = app(CurrentTenant::class)->run($tenantA, function () use ($tenantA) {
        return \App\Domain\Business\Models\BusinessProfile::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => ['en' => 'A', 'ar' => 'أ'],
        ]);
    });

    app(CurrentTenant::class)->set($tenantB);

    expect(fn () => $profileA->update(['name' => ['en' => 'Hacked', 'ar' => 'مخترق']]))
        ->toThrow(LogicException::class);

    expect(fn () => $profileA->delete())
        ->toThrow(LogicException::class);
});

test('tenant role names are isolated between workspaces', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $a = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Alpha Clinic'],
    );

    $b = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Beta Clinic'],
    );

    expect(Role::query()->where('name', 'owner')->where('tenant_id', $a->id)->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'owner')->where('tenant_id', $b->id)->exists())->toBeTrue()
        ->and($a->id)->not->toBe($b->id);
});

test('workspace configuration page is available for the active tenant', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Workspace UI Clinic'],
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('onboarding.workspace'))
        ->assertOk()
        ->assertSee(__('app.configure_workspace'))
        ->assertSee(__('app.choose_modules'));
});

test('module onboarding always keeps core modules enabled', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Core Modules Clinic'],
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('onboarding.workspace.modules'), [
            'module_ids' => [],
        ])
        ->assertRedirect(route('services.index'))
        ->assertSessionHasNoErrors();

    $coreModuleIds = Module::query()->where('is_core', true)->pluck('id');

    expect(
        $tenant->modules()->whereIn('module_id', $coreModuleIds)->wherePivot('enabled', true)->count()
    )->toBe($coreModuleIds->count());
});

test('onboarding blocks skipping required setup steps', function (): void {
    $owner = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $owner,
        $type,
        ['name' => 'Sequential Clinic'],
    );

    $session = ['tenant_id' => $tenant->id];

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('services.index'))
        ->assertRedirect(route('onboarding.workspace'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('scheduling.index'))
        ->assertRedirect(route('onboarding.workspace'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('staff.index'))
        ->assertRedirect(route('onboarding.workspace'));

    $this->actingAs($owner)
        ->withSession($session)
        ->post(route('onboarding.workspace.modules'), [
            'module_ids' => [],
        ])
        ->assertRedirect(route('services.index'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('scheduling.index'))
        ->assertRedirect(route('services.index'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('staff.index'))
        ->assertRedirect(route('services.index'));

    $this->actingAs($owner)
        ->withSession($session)
        ->post(route('services.store'), [
            'name_en' => 'Consultation',
            'name_ar' => 'كشف',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ])
        ->assertRedirect(route('scheduling.index'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('staff.index'))
        ->assertRedirect(route('scheduling.index'));

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
        'is_closed' => false,
    ])->all();

    $this->actingAs($owner)
        ->withSession($session)
        ->put(route('scheduling.business-hours.update'), ['hours' => $hours])
        ->assertRedirect(route('staff.index'));

    $this->actingAs($owner)
        ->withSession($session)
        ->get(route('staff.index'))
        ->assertOk();
});

test('onboarding advances through services working hours and staff stages', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'onboarding-staff@example.com']);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $owner,
        $type,
        ['name' => 'Progress Clinic'],
    );

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store'), [
            'name_en' => 'Consultation',
            'name_ar' => 'كشف',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ])
        ->assertRedirect(route('scheduling.index'));

    expect(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('hours');

    $hours = collect(range(1, 7))->map(fn (int $day): array => [
        'day_of_week' => $day,
        'opens_at' => '09:00',
        'closes_at' => '17:00',
        'is_closed' => false,
    ])->all();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->put(route('scheduling.business-hours.update'), ['hours' => $hours])
        ->assertRedirect(route('staff.index'));

    expect(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('staff');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('staff.store'), [
            'email' => $staffUser->email,
            'display_name' => 'Onboarding Staff',
            'role' => 'staff',
        ])
        ->assertRedirect(route('onboarding.workspace'));

    expect(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('ready');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('onboarding.complete'))
        ->assertRedirect(route('dashboard'));

    expect(data_get($tenant->fresh()->settings, 'onboarding.completed'))->toBeTrue();
});

test('onboarding cannot complete when a core module is disabled', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create(['email' => 'core-module-staff@example.com']);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $owner,
        $type,
        ['name' => 'Core Guard Clinic'],
    );

    app(CurrentTenant::class)->run($tenant, function () use ($tenant, $staffUser): void {
        \App\Domain\Service\Models\Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
            'description' => ['en' => null, 'ar' => null],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        \App\Domain\Scheduling\Models\BusinessWorkingHour::query()->create([
            'tenant_id' => $tenant->id,
            'day_of_week' => \App\Domain\Scheduling\Enums\DayOfWeek::Sunday,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);

        \App\Domain\Staff\Models\StaffProfile::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staffUser->id,
            'display_name' => 'Core Guard Staff',
            'status' => \App\Domain\Staff\Enums\StaffStatus::Active,
        ]);
    });

    $coreModule = Module::query()->where('is_core', true)->where('key', 'services')->firstOrFail();

    $tenant->modules()->updateExistingPivot($coreModule->id, ['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('onboarding.complete'))
        ->assertSessionHasErrors('onboarding');

    expect(data_get($tenant->fresh()->settings, 'onboarding.completed', false))->toBeFalse();
});

test('onboarding cannot be completed before services working hours and staff exist', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $user,
        $type,
        ['name' => 'Incomplete Clinic'],
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('onboarding.complete'))
        ->assertSessionHasErrors('onboarding');

    expect((bool) data_get($tenant->fresh()->settings, 'onboarding.completed', false))->toBeFalse();
});

test('onboarding completes when workspace modules services working hours and staff are ready', function (): void {
    $owner = User::factory()->create();
    $staffUser = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(\App\Domain\Business\Actions\CreateBusiness::class)->handle(
        $owner,
        $type,
        ['name' => 'Ready Clinic'],
    );

    app(CurrentTenant::class)->run($tenant, function () use ($tenant, $owner, $staffUser): void {
        \App\Domain\Service\Models\Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Consultation', 'ar' => 'كشف'],
            'description' => ['en' => null, 'ar' => null],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        \App\Domain\Scheduling\Models\BusinessWorkingHour::query()->create([
            'tenant_id' => $tenant->id,
            'day_of_week' => \App\Domain\Scheduling\Enums\DayOfWeek::Sunday,
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'is_closed' => false,
        ]);

        \App\Domain\Staff\Models\StaffProfile::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $staffUser->id,
            'display_name' => 'Staff One',
            'status' => \App\Domain\Staff\Enums\StaffStatus::Active,
        ]);
    });

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('onboarding.complete'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    expect((bool) data_get($tenant->fresh()->settings, 'onboarding.completed', false))->toBeTrue()
        ->and(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('ready');
});
