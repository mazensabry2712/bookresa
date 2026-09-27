<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Actions\SyncServiceAssignments;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
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

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function serviceWorkspaceOwner(): array
{
    $user = User::factory()->create([
        'name' => 'Service Workspace Owner',
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Service Clinic',
        'name_en' => 'Service Clinic',
        'name_ar' => 'عيادة الخدمات',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    app(CurrentTenant::class)->set($tenant);

    return [$user, $tenant];
}

function serviceUiService(string $name, bool $active = true): Service
{
    return app(CreateService::class)->handle([
        'name' => ['en' => $name, 'ar' => $name],
        'description' => ['en' => $name.' description', 'ar' => $name.' description'],
        'price_minor' => 15000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 10,
        'is_active' => $active,
    ]);
}

test('owner can search and filter services and see assigned staff count', function (): void {
    [$owner, $tenant] = serviceWorkspaceOwner();

    $consultation = serviceUiService('Consultation');
    $inactive = serviceUiService('Follow Up', false);

    $staffUser = User::factory()->create([
        'name' => 'Dr. Service',
        'email' => 'dr-service@example.com',
    ]);

    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff', [
        'display_name' => 'Dr. Service',
    ]);

    app(SyncServiceAssignments::class)->handle($staff, [$consultation->id]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', [
            'tenant' => $tenant->slug,
            'search' => 'Consultation',
        ]))
        ->assertOk()
        ->assertSee('Consultation')
        ->assertDontSee('Follow Up')
        ->assertSee('1 assigned staff member');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', [
            'tenant' => $tenant->slug,
            'status' => 'inactive',
        ]))
        ->assertOk()
        ->assertSee('Follow Up')
        ->assertDontSee('Consultation');
});

test('service list paginates large service lists', function (): void {
    [$owner, $tenant] = serviceWorkspaceOwner();

    for ($i = 1; $i <= 21; $i++) {
        serviceUiService('Service '.$i);
    }

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertViewHas('services', function ($services): bool {
            return $services->perPage() === 20
                && $services->total() === 21;
        });
});
