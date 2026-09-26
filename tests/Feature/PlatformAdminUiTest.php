<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function platformUiAdmin(): User
{
    $user = User::factory()->create([
        'name' => 'Platform UI Admin',
        'email' => 'platform-ui-admin@example.com',
    ]);

    PlatformAdmin::query()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $user;
}

test('platform admin can render every primary admin page', function (): void {
    $admin = platformUiAdmin();

    foreach ([
        'admin.dashboard',
        'admin.businesses.index',
        'admin.users.index',
        'admin.subscriptions.index',
        'admin.payments.index',
        'admin.usage.index',
        'admin.reports.index',
        'admin.support.index',
        'admin.settings.index',
        'admin.plans.index',
    ] as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk();
    }
});

test('platform admin can render workspace module management from admin navigation', function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);

    $admin = platformUiAdmin();
    $owner = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Admin UI Clinic',
        'name_en' => 'Admin UI Clinic',
        'name_ar' => 'عيادة إدارة',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.businesses.modules.index', $tenant))
        ->assertOk()
        ->assertSee('Admin UI Clinic')
        ->assertSee('Appointments')
        ->assertSee('Payments');
});

test('non platform admin cannot render the admin interface', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
