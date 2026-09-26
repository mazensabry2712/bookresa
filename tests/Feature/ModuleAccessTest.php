<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

function moduleWorkspace(string $name = 'Module Clinic'): array
{
    $owner = User::factory()->create([
        'name' => $name.' Owner',
    ]);

    $businessType = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $businessType, [
        'name' => $name,
        'name_en' => $name,
        'name_ar' => $name,
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$owner, $tenant];
}

test('enabled modules allow authorized tenant routes', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index'))
        ->assertOk();
});

test('disabled service module blocks service routes even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'services')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store'), [
            'name_en' => 'Blocked',
            'name_ar' => 'محجوبة',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertForbidden();
});

test('disabled staff module blocks staff routes even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'staff')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('staff.index'))
        ->assertForbidden();
});

test('disabled calendar module blocks calendar route even with the permission', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'calendar')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index'))
        ->assertForbidden();
});

test('disabled appointments module blocks booking management and public booking', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'appointments')->firstOrFail();
    TenantModule::query()
        ->where('module_id', $module->id)
        ->update(['enabled' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index'))
        ->assertForbidden();

    $this->get(route('public.booking.show', ['tenant' => $tenant->slug]))
        ->assertForbidden();
});

test('missing optional tenant module row does not grant access', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    Route::middleware(['web', 'auth', 'tenant', 'module:payments'])
        ->get('/__test/optional-payments-module', fn () => 'ok');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get('/__test/optional-payments-module')
        ->assertForbidden();
});

test('inactive global module blocks tenant access even when tenant module is enabled', function (): void {
    [$owner, $tenant] = moduleWorkspace();

    app(CurrentTenant::class)->set($tenant);

    $module = Module::query()->where('key', 'services')->firstOrFail();
    $module->update(['is_active' => false]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertForbidden();
});
