<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Service\Models\Service;
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

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function workspaceOwner(string $name = 'Workspace Owner'): array
{
    $user = User::factory()->create([
        'name' => $name,
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Owner Clinic',
        'name_en' => 'Owner Clinic',
        'name_ar' => 'عيادة المالك',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    return [$user, $tenant];
}

test('owner can view and update business profile', function (): void {
    [$user, $tenant] = workspaceOwner();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('business.profile.edit'))
        ->assertOk()
        ->assertSee('Business profile')
        ->assertSee('Owner Clinic')
        ->assertSee('Minimum notice')
        ->assertSee('Maximum advance');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->put(route('business.profile.update'), [
            'name_en' => 'Updated Clinic',
            'name_ar' => 'العيادة المحدثة',
            'description_en' => 'Updated description',
            'description_ar' => 'وصف محدث',
            'phone' => '01012345678',
            'email' => 'clinic@example.com',
            'location' => 'Cairo',
            'address' => '123 Main Street',
            'website' => 'https://example.com',
            'facebook' => '',
            'instagram' => '',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
            'payment_mode' => 'pay_later',
            'customer_email_required' => true,
            'customer_limit_policy' => 'allow_overage',
            'minimum_notice_minutes' => 60,
            'maximum_advance_days' => 90,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    app(CurrentTenant::class)->set($tenant);

    expect($tenant->fresh('profile')->profile->name['en'])->toBe('Updated Clinic')
        ->and($tenant->fresh('profile')->profile->locale)->toBe('ar')
        ->and($tenant->fresh('profile')->profile->phone)->toBe('01012345678')
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'minimum_notice_minutes'))->toBe(60)
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'maximum_advance_days'))->toBe(90)
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'customer_email_required'))->toBeTrue();
});

test('owner can create and update a service through workspace ui', function (): void {
    [$user, $tenant] = workspaceOwner();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store'), [
            'name_en' => 'Haircut',
            'name_ar' => 'حلاقة',
            'description_en' => 'Classic haircut',
            'description_ar' => 'حلاقة كلاسيكية',
            'price' => '150.00',
            'currency' => 'egp',
            'duration_minutes' => 30,
            'buffer_minutes' => 10,
            'is_active' => 1,
        ])
        ->assertRedirect(route('services.index'));

    app(CurrentTenant::class)->set($tenant);

    $service = Service::query()->firstOrFail();

    expect($service->tenant_id)->toBe($tenant->id)
        ->and($service->price_minor)->toBe(15000)
        ->and($service->currency)->toBe('EGP');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->put(route('services.update', $service), [
            'name_en' => 'Premium Haircut',
            'name_ar' => 'حلاقة فاخرة',
            'description_en' => 'Premium',
            'description_ar' => '',
            'price' => '175.50',
            'currency' => 'EGP',
            'duration_minutes' => 45,
            'buffer_minutes' => 5,
            'is_active' => 1,
        ])
        ->assertRedirect(route('services.index'));

    expect($service->fresh()->price_minor)->toBe(17550)
        ->and($service->fresh()->duration_minutes)->toBe(45);
});

test('workspace service management stays tenant isolated', function (): void {
    [$user, $tenantA] = workspaceOwner('Tenant A Owner');
    [$otherUser, $tenantB] = workspaceOwner('Tenant B Owner');

    $this->actingAs($user)->withSession(['tenant_id' => $tenantA->id])
        ->post(route('services.store'), [
            'name_en' => 'Tenant A Service',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 20,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ]);

    app(CurrentTenant::class)->set($tenantA);

    $serviceA = Service::query()->firstOrFail();

    $this->actingAs($otherUser)->withSession(['tenant_id' => $tenantB->id])
        ->get(route('services.index', ['edit' => $serviceA->id]))
        ->assertNotFound();

    app(CurrentTenant::class)->set($tenantB);

    expect(Service::query()->count())->toBe(0);
    expect($otherUser->tenantMemberships()->where('tenant_id', $tenantB->id)->exists())->toBeTrue();
});

test('receptionist cannot manage services', function (): void {
    [$owner, $tenant] = workspaceOwner();
    $staffUser = User::factory()->create();

    app(\App\Domain\Tenant\Services\CurrentTenant::class)->set($tenant);
    app(\App\Domain\Staff\Actions\AddStaffMember::class)->handle($staffUser, 'receptionist');

    $this->actingAs($staffUser)->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store'), [
            'name_en' => 'Blocked',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 20,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertForbidden();

    expect(Service::query()->count())->toBe(0);

    unset($owner);
});

test('service delete is blocked when booking history exists', function (): void {
    [$user, $tenant] = workspaceOwner();
    app(\App\Domain\Tenant\Services\CurrentTenant::class)->set($tenant);

    $service = app(\App\Domain\Service\Actions\CreateService::class)->handle([
        'name' => ['en' => 'Booked Service', 'ar' => 'خدمة محجوزة'],
        'price_minor' => 10000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    // The actual booking domain test suite owns booking creation; this test
    // verifies the management UI can never delete a referenced service once
    // a booking record exists.
    \Illuminate\Support\Facades\DB::table('bookings')->insert([
        'tenant_id' => $tenant->id,
        'customer_id' => \App\Domain\Customer\Models\Customer::factory()->create()->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 10:30:00',
        'block_ends_at' => '2026-10-05 10:30:00',
        'status' => 'confirmed',
        'payment_status' => 'unpaid',
        'booking_reference' => 'BR-TEST-DELETE-1',
        'notes' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->delete(route('services.destroy', $service))
        ->assertRedirect()
        ->assertSessionHasErrors('service');

    expect($service->fresh())->not->toBeNull()
        ->and($service->fresh()->is_active)->toBeTrue();
});


test('service management paginates large service lists', function (): void {
    [$user, $tenant] = workspaceOwner();

    app(CurrentTenant::class)->set($tenant);

    for ($i = 1; $i <= 21; $i++) {
        Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Service '.$i, 'ar' => 'خدمة '.$i],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);
    }

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index'))
        ->assertOk()
        ->assertSee('Service 21')
        ->assertDontSee('<p class="font-semibold">Service 1</p>');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Service 1');
});
