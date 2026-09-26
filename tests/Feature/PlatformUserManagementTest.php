<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
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

function platformUsersAdmin(bool $active = true): User
{
    $user = User::factory()->create();

    if ($active) {
        PlatformAdmin::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    return $user;
}

function platformUsersBusiness(User $owner, string $name): \App\Domain\Tenant\Models\Tenant
{
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    return app(CreateBusiness::class)->handle($owner, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);
}

test('non platform admin cannot access user management', function (): void {
    $user = platformUsersAdmin(false);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('platform admin can list users and workspace memberships', function (): void {
    $admin = platformUsersAdmin();
    $ownerA = User::factory()->create(['name' => 'Alpha Owner', 'email' => 'alpha-owner@example.com']);
    $ownerB = User::factory()->create(['name' => 'Beta Owner', 'email' => 'beta-owner@example.com']);

    $tenantA = platformUsersBusiness($ownerA, 'Alpha Clinic');
    $tenantB = platformUsersBusiness($ownerB, 'Beta Salon');

    $member = User::factory()->create(['name' => 'Shared Member', 'email' => 'shared@example.com']);
    TenantMembership::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $member->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);
    TenantMembership::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $member->id,
        'status' => MembershipStatus::Suspended,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Alpha Owner')
        ->assertSee('Beta Owner')
        ->assertSee('Shared Member')
        ->assertSee('Alpha Clinic')
        ->assertSee('Beta Salon');

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Shared']))
        ->assertOk()
        ->assertSee('Shared Member')
        ->assertDontSee('Alpha Owner')
        ->assertDontSee('Beta Owner');
});

test('platform admin can activate and suspend memberships', function (): void {
    $admin = platformUsersAdmin();
    $owner = User::factory()->create(['email' => 'membership-owner@example.com']);
    $tenant = platformUsersBusiness($owner, 'Membership Clinic');

    $membership = $owner->tenantMemberships()->where('tenant_id', $tenant->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.users.membership-toggle', $membership))
        ->assertRedirect()
        ->assertSessionHas('status', 'User membership suspended successfully.');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Suspended);

    $this->actingAs($admin)
        ->patch(route('admin.users.membership-toggle', $membership))
        ->assertRedirect()
        ->assertSessionHas('status', 'User membership activated successfully.');

    expect($membership->fresh()->status)->toBe(MembershipStatus::Active);
});

test('platform admin can grant and disable platform admin access', function (): void {
    $admin = platformUsersAdmin();
    $target = User::factory()->create(['email' => 'target-admin@example.com']);

    $this->actingAs($admin)
        ->patch(route('admin.users.platform-admin-toggle', $target))
        ->assertRedirect()
        ->assertSessionHas('status', 'Platform admin access activated successfully.');

    expect($target->platformAdmin->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.users.platform-admin-toggle', $target))
        ->assertRedirect()
        ->assertSessionHas('status', 'Platform admin access deactivated successfully.');

    expect($target->platformAdmin->fresh()->is_active)->toBeFalse();
});

test('platform admin cannot deactivate itself or the last active platform admin', function (): void {
    $admin = platformUsersAdmin();

    $this->actingAs($admin)
        ->patch(route('admin.users.platform-admin-toggle', $admin))
        ->assertRedirect()
        ->assertSessionHasErrors('user');

    $other = User::factory()->create();
    PlatformAdmin::query()->create([
        'user_id' => $other->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.users.platform-admin-toggle', $other))
        ->assertRedirect()
        ->assertSessionHas('status', 'Platform admin access deactivated successfully.');
});
