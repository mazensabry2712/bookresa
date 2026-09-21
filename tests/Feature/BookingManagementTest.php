<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Business\Models\BusinessProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function managementTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

function managementUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    setPermissionsTeamId($tenant->id);

    $permissions = [
        'bookings.view',
        'bookings.update',
        'bookings.cancel',
        'bookings.complete',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $role = Role::firstOrCreate([
        'name' => 'management-owner',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);

    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

function managementBooking(Tenant $tenant, string $reference = 'BR-MGMT-001'): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);


    return Booking::query()->create([
        'customer_id' => Customer::query()->create([
            'name' => 'Customer One',
            'phone' => '01000000999',
            'normalized_phone' => '201000000999',
        ])->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => CarbonImmutable::parse('2026-09-28 10:00', 'Africa/Cairo')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-09-28 10:30', 'Africa/Cairo')->utc(),
        'block_ends_at' => CarbonImmutable::parse('2026-09-28 10:30', 'Africa/Cairo')->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

test('authorized tenant user can list and filter bookings', function (): void {
    $tenant = managementTenant('management-list');
    $user = managementUser($tenant, 'management-list@example.com');
    $booking = managementBooking($tenant);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index', ['status' => 'pending', 'search' => $booking->booking_reference]))
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee('Customer One');
});

test('booking management is tenant isolated', function (): void {
    $tenantA = managementTenant('management-a');
    $userA = managementUser($tenantA, 'management-a@example.com');
    $bookingA = managementBooking($tenantA, 'BR-MGMT-A');

    $tenantB = managementTenant('management-b');
    managementBooking($tenantB, 'BR-MGMT-B');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('booking.management.index'))
        ->assertOk()
        ->assertSee($bookingA->booking_reference)
        ->assertDontSee('BR-MGMT-B');
});

test('booking detail is tenant scoped', function (): void {
    $tenantA = managementTenant('management-detail-a');
    $userA = managementUser($tenantA, 'management-detail-a@example.com');
    $bookingA = managementBooking($tenantA, 'BR-MGMT-DETAIL-A');

    $tenantB = managementTenant('management-detail-b');
    $bookingB = managementBooking($tenantB, 'BR-MGMT-DETAIL-B');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('booking.management.show', $bookingB))
        ->assertNotFound();

    expect($bookingA->exists)->toBeTrue();
});

test('authorized user can confirm and history is recorded', function (): void {
    $tenant = managementTenant('management-status');
    $user = managementUser($tenant, 'management-status@example.com');
    $booking = managementBooking($tenant, 'BR-MGMT-STATUS');

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('booking.management.status', $booking), [
            'status' => 'confirmed',
        ])
        ->assertRedirect(route('booking.management.show', $booking));

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->statusHistory()->count())->toBe(2);
});

test('user without booking permission is forbidden', function (): void {
    $tenant = managementTenant('management-forbidden');
    $user = User::factory()->create(['email' => 'management-forbidden@example.com']);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index'))
        ->assertForbidden();
});
