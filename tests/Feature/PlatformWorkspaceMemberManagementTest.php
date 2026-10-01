<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
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

test('platform admin can add update transfer ownership and remove workspace members', function (): void {
    $admin = User::factory()->create();
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $owner = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();
    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Members Control Clinic',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.businesses.members.store', $tenant), [
            'name' => 'Reception Member',
            'email' => 'reception-member@example.com',
            'password' => 'ReceptionMember!2026',
            'password_confirmation' => 'ReceptionMember!2026',
            'role' => 'receptionist',
        ])
        ->assertRedirect();

    $membership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->whereHas('user', fn ($query) => $query->where('email', 'reception-member@example.com'))
        ->firstOrFail();

    expect($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->is_primary)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.businesses.members.update', [$tenant, $membership]), [
            'role' => 'manager',
            'status' => 'active',
            'is_primary' => '1',
        ])
        ->assertRedirect();

    expect($membership->fresh()->is_primary)->toBeTrue();

    $ownerMembership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    expect($ownerMembership->fresh()->is_primary)->toBeFalse();

    $this->actingAs($admin)
        ->patch(route('admin.businesses.members.update', [$tenant, $ownerMembership]), [
            'role' => 'owner',
            'status' => 'active',
            'is_primary' => '1',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->delete(route('admin.businesses.members.destroy', [$tenant, $ownerMembership]))
        ->assertSessionHasErrors('membership');

    $memberUser = User::query()->where('email', 'reception-member@example.com')->firstOrFail();
    $memberMembership = TenantMembership::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $memberUser->id)
        ->firstOrFail();

    expect($memberMembership->fresh()->is_primary)->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.businesses.members.store', $tenant), [
            'name' => $memberUser->name,
            'email' => $memberUser->email,
            'role' => 'receptionist',
            'is_primary' => '',
        ])
        ->assertRedirect();

    expect(TenantMembership::query()->where('tenant_id', $tenant->id)->where('user_id', $memberUser->id)->count())->toBe(1);

    $this->actingAs($admin)
        ->patch(route('admin.businesses.members.update', [$tenant, $memberMembership]), [
            'role' => 'receptionist',
            'status' => 'active',
            'is_primary' => '1',
        ])
        ->assertRedirect();

    // The primary owner can only be changed explicitly, never removed accidentally.
    expect($memberMembership->fresh()->is_primary)->toBeTrue();
});

test('non platform admin cannot mutate workspace members', function (): void {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Protected Members Clinic',
    ]);

    $this->actingAs($user)
        ->post(route('admin.businesses.members.store', $tenant), [
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'password' => 'BlockedMember!2026',
            'password_confirmation' => 'BlockedMember!2026',
            'role' => 'staff',
        ])
        ->assertForbidden();
});
