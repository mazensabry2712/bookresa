<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
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

function workspaceControlAdmin(): User
{
    $user = User::factory()->create();

    PlatformAdmin::query()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $user;
}

test('platform admin can create update and delete a workspace', function (): void {
    $admin = workspaceControlAdmin();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.businesses.store'), [
            'owner_name' => 'Platform Created Owner',
            'owner_email' => 'platform-created-owner@example.com',
            'owner_password' => 'PlatformCreated!2026',
            'owner_password_confirmation' => 'PlatformCreated!2026',
            'business_name_en' => 'Platform Created Clinic',
            'business_name_ar' => 'عيادة تم إنشاؤها',
            'business_type_id' => $type->id,
            'slug' => 'platform-created-clinic',
            'phone' => '+201000000001',
            'email' => 'clinic@example.com',
            'timezone' => 'Africa/Cairo',
            'locale' => 'en',
            'status' => 'active',
        ])
        ->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'platform-created-clinic')->firstOrFail();
    $owner = User::query()->where('email', 'platform-created-owner@example.com')->firstOrFail();

    $profile = BusinessProfile::withoutGlobalScopes()
        ->where('tenant_id', $tenant->getKey())
        ->firstOrFail();

    expect($tenant->status)->toBe(TenantStatus::Active)
        ->and($profile->email)->toBe('clinic@example.com')
        ->and($owner->hasVerifiedEmail())->toBeTrue();

    $this->actingAs($admin)
        ->put(route('admin.businesses.update', $tenant), [
            'business_name_en' => 'Updated Clinic',
            'business_name_ar' => 'عيادة محدثة',
            'business_type_id' => $type->id,
            'slug' => 'updated-clinic',
            'phone' => '+201000000002',
            'email' => 'updated@example.com',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
            'status' => 'suspended',
        ])
        ->assertRedirect();

    $tenant->refresh();
    $updatedProfile = BusinessProfile::withoutGlobalScopes()
        ->where('tenant_id', $tenant->getKey())
        ->firstOrFail();

    expect($tenant->slug)->toBe('updated-clinic')
        ->and($tenant->status)->toBe(TenantStatus::Suspended)
        ->and(data_get($updatedProfile->name, 'en'))->toBe('Updated Clinic')
        ->and($updatedProfile->locale)->toBe('ar');

    $this->actingAs($admin)
        ->delete(route('admin.businesses.destroy', $tenant))
        ->assertRedirect(route('admin.businesses.index'));

    expect(Tenant::query()->whereKey($tenant->id)->exists())->toBeFalse();
    expect(User::query()->whereKey($owner->id)->exists())->toBeTrue();
});

test('non platform admin cannot use workspace control mutations', function (): void {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $tenant = app(CreateBusiness::class)->handle(
        $owner,
        BusinessType::query()->where('slug', 'clinic')->firstOrFail(),
        ['name' => 'Protected Workspace'],
    );

    $this->actingAs($user)
        ->put(route('admin.businesses.update', $tenant), [
            'business_name_en' => 'Blocked',
            'business_type_id' => $tenant->business_type_id,
            'slug' => $tenant->slug,
            'timezone' => 'Africa/Cairo',
            'locale' => 'en',
            'status' => 'active',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('admin.businesses.destroy', $tenant))
        ->assertForbidden();
});
