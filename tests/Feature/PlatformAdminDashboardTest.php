<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
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

function adminDashboardUser(bool $admin = true): User
{
    $user = User::factory()->create();

    if ($admin) {
        PlatformAdmin::query()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    return $user;
}

function adminDashboardTenant(User $owner, string $name, TenantStatus $status = TenantStatus::Active): Tenant
{
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    if ($status !== TenantStatus::Active) {
        $tenant->forceFill(['status' => $status])->save();
    }

    return $tenant->fresh();
}

test('non platform admin cannot access platform dashboard or businesses', function (): void {
    $user = adminDashboardUser(false);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.businesses.index'))
        ->assertForbidden();
});

test('platform admin can view dashboard metrics', function (): void {
    $admin = adminDashboardUser();

    $ownerA = User::factory()->create(['email' => 'admin-dashboard-owner-a@example.com']);
    $ownerB = User::factory()->create(['email' => 'admin-dashboard-owner-b@example.com']);

    adminDashboardTenant($ownerA, 'Admin Clinic A');
    adminDashboardTenant($ownerB, 'Admin Clinic B', TenantStatus::Suspended);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Admin Dashboard')
        ->assertViewHas('metrics', function (array $metrics): bool {
            return $metrics['businesses'] === 2
                && $metrics['activeBusinesses'] === 1
                && $metrics['suspendedBusinesses'] === 1;
        })
        ->assertViewHas('recentBusinesses', function ($businesses): bool {
            return $businesses->count() === 2;
        });
});

test('platform admin can list and search businesses', function (): void {
    $admin = adminDashboardUser();

    $ownerA = User::factory()->create(['email' => 'search-owner-a@example.com']);
    $ownerB = User::factory()->create(['email' => 'search-owner-b@example.com']);

    adminDashboardTenant($ownerA, 'Alpha Dental');
    adminDashboardTenant($ownerB, 'Beta Salon');

    $this->actingAs($admin)
        ->get(route('admin.businesses.index'))
        ->assertOk()
        ->assertSee('Alpha Dental')
        ->assertSee('Beta Salon')
        ->assertSee('1');

    $this->actingAs($admin)
        ->get(route('admin.businesses.index', ['search' => 'Alpha']))
        ->assertOk()
        ->assertSee('Alpha Dental')
        ->assertDontSee('Beta Salon');
});

test('platform admin can suspend and reactivate a business', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'status-owner@example.com']);
    $tenant = adminDashboardTenant($owner, 'Status Clinic');

    $this->actingAs($admin)
        ->patch(route('admin.businesses.toggle-status', $tenant))
        ->assertRedirect()
        ->assertSessionHas('status', 'Business suspended successfully.');

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->actingAs($admin)
        ->patch(route('admin.businesses.toggle-status', $tenant))
        ->assertRedirect()
        ->assertSessionHas('status', 'Business activated successfully.');

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});

test('platform admin can view subscriptions payments and usage', function (): void {
    $admin = adminDashboardUser();

    $this->actingAs($admin)
        ->get(route('admin.subscriptions.index'))
        ->assertOk()
        ->assertSee('Subscriptions');

    $this->actingAs($admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('Payments');

    $this->actingAs($admin)
        ->get(route('admin.usage.index'))
        ->assertOk()
        ->assertSee('Usage');
});

test('platform finance pages reject non platform admins', function (): void {
    $user = adminDashboardUser(false);

    $this->actingAs($user)
        ->get(route('admin.subscriptions.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.payments.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.usage.index'))
        ->assertForbidden();
});

test('platform routes remain independent from current tenant context', function (): void {
    $admin = adminDashboardUser();
    $owner = User::factory()->create(['email' => 'independent-owner@example.com']);
    adminDashboardTenant($owner, 'Independent Clinic');

    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(app(CurrentTenant::class)->get())->toBeNull();
});
