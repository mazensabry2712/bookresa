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

    expect($ownerRole->permissions->pluck('name')->contains('bookings.create'))->toBeTrue();
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
