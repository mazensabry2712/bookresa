<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use App\Support\AuditLogger;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

function platformCoreAdmin(): User
{
    $user = User::factory()->create();

    PlatformAdmin::query()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $user;
}

function platformCoreTenant(string $name, ?User $owner = null, TenantStatus $status = TenantStatus::Active): Tenant
{
    $owner ??= User::factory()->create();
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

test('platform security and audit consoles reject non platform admins', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.audit.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.security.index'))
        ->assertForbidden();
});

test('platform admin can search the security audit trail by workspace', function (): void {
    $admin = platformCoreAdmin();
    $tenant = platformCoreTenant('Audit Trail Clinic');

    $this->actingAs($admin);

    app(AuditLogger::class)->log(
        'platform.workspace_tested',
        $tenant,
        ['tenant_id' => $tenant->id, 'status' => 'active'],
    );

    $this->get(route('admin.audit.index', ['tenant_id' => $tenant->id]))
        ->assertOk()
        ->assertSee('platform.workspace_tested')
        ->assertSee('Audit Trail Clinic')
        ->assertSee($admin->name);
});

test('platform admin can view security posture and platform admin 2fa status', function (): void {
    $admin = platformCoreAdmin();
    $admin->forceFill([
        'two_factor_secret' => 'encrypted-secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($admin)
        ->get(route('admin.security.index'))
        ->assertOk()
        ->assertSee(__('platform.platform_security'))
        ->assertSee(__('platform.enabled'))
        ->assertSee($admin->email);
});

test('platform admin can impersonate an active verified workspace member and return safely', function (): void {
    $admin = platformCoreAdmin();
    $owner = User::factory()->create();
    $tenant = platformCoreTenant('Impersonation Clinic', $owner);
    $target = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $target->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.businesses.impersonate', [$tenant, $target]))
        ->assertRedirect(route('dashboard', ['tenant' => $tenant->slug]));

    expect(auth()->id())->toBe($target->id)
        ->and(session('platform_impersonator_id'))->toBe($admin->id)
        ->and(session('platform_impersonated_user_id'))->toBe($target->id)
        ->and(session('platform_impersonated_tenant_id'))->toBe($tenant->id);

    $this->get(route('dashboard', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee(__('platform.impersonation_active'))
        ->assertSee(__('platform.stop_impersonation'));

    $this->post(route('platform.impersonation.stop'))
        ->assertRedirect(route('admin.dashboard'));

    expect(auth()->id())->toBe($admin->id)
        ->and(session('platform_impersonator_id'))->toBeNull()
        ->and(session('platform_impersonated_user_id'))->toBeNull();

    expect(Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'platform.impersonation_started')
        ->exists())->toBeTrue();

    expect(Activity::query()
        ->where('log_name', 'security')
        ->where('description', 'platform.impersonation_stopped')
        ->exists())->toBeTrue();
});

test('impersonation cannot target an inactive member or another platform admin', function (): void {
    $admin = platformCoreAdmin();
    $tenant = platformCoreTenant('Restricted Impersonation Clinic');

    $inactive = User::factory()->create(['email_verified_at' => now()]);
    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $inactive->id,
        'status' => MembershipStatus::Suspended,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.businesses.impersonate', [$tenant, $inactive]))
        ->assertSessionHasErrors('user');

    $targetAdmin = platformCoreAdmin();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $targetAdmin->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.businesses.impersonate', [$tenant, $targetAdmin]))
        ->assertForbidden();
});

test('a disabled original platform admin cannot reclaim an impersonation session', function (): void {
    $admin = platformCoreAdmin();
    $owner = User::factory()->create();
    $tenant = platformCoreTenant('Reclaim Clinic', $owner);
    $target = User::factory()->create(['email_verified_at' => now()]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $target->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.businesses.impersonate', [$tenant, $target]))
        ->assertRedirect();

    PlatformAdmin::query()
        ->where('user_id', $admin->id)
        ->update(['is_active' => false]);

    $this->post(route('platform.impersonation.stop'))
        ->assertForbidden();
});
