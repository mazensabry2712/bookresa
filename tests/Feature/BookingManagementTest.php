<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Staff\Models\StaffProfile;
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

function managementBooking(
    Tenant $tenant,
    string $reference = 'BR-MGMT-001',
    string $customerName = 'Customer One',
    string $phone = '01000000999',
): Booking
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
            'name' => $customerName,
            'phone' => $phone,
            'normalized_phone' => preg_replace('/\D+/', '', str_starts_with($phone, '+20') ? '20'.substr(preg_replace('/\D+/', '', $phone), 2) : '20'.ltrim(preg_replace('/\D+/', '', $phone), '0')),
        ])->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'service_name_snapshot' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'service_price_minor_snapshot' => 25000,
        'service_currency_snapshot' => 'EGP',
        'service_duration_minutes_snapshot' => 30,
        'service_buffer_minutes_snapshot' => 0,
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
        ->get(route('booking.management.index', ['tenant' => $tenant->slug, 'status' => 'pending', 'search' => $booking->booking_reference]))
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertSee('Customer One');
});

test('booking list can filter by payment status', function (): void {
    $tenant = managementTenant('management-payment-filter');
    $user = managementUser($tenant, 'management-payment-filter@example.com');
    $unpaid = managementBooking($tenant, 'BR-MGMT-UNPAID', 'Unpaid Customer', '01000000999');
    $paid = managementBooking($tenant, 'BR-MGMT-PAID', 'Paid Customer', '01000000888');
    $paid->update(['payment_status' => 'paid']);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index', [
            'tenant' => $tenant->slug,
            'payment_status' => 'paid',
        ]))
        ->assertOk()
        ->assertSee($paid->booking_reference)
        ->assertDontSee($unpaid->booking_reference);
});

test('booking search matches service snapshot, staff and notes', function (): void {
    $tenant = managementTenant('management-expanded-search');
    $user = managementUser($tenant, 'management-expanded-search@example.com');
    $booking = managementBooking($tenant, 'BR-MGMT-SEARCH-EXTRA', 'Searchable Customer', '01000000877');

    app(CurrentTenant::class)->set($tenant);

    $booking->update([
        'service_name_snapshot' => ['en' => 'Premium Haircut', 'ar' => 'قص شعر مميز'],
        'notes' => 'VIP customer requests a quiet room',
    ]);

    $staffUser = User::factory()->create(['email' => 'management-search-staff@example.com']);
    $staff = StaffProfile::query()->create([
        'user_id' => $staffUser->id,
        'display_name' => 'Mona Stylist',
        'status' => StaffStatus::Active,
    ]);
    $booking->update(['staff_id' => $staff->getKey()]);

    foreach (['Premium Haircut', 'Mona Stylist', 'quiet room', '01000000877'] as $term) {
        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('booking.management.index', [
                'tenant' => $tenant->slug,
                'search' => $term,
            ]))
            ->assertOk()
            ->assertSee($booking->booking_reference);
    }
});

test('booking list supports an inclusive local date range and rejects reversed ranges', function (): void {
    $tenant = managementTenant('management-date-range');
    $user = managementUser($tenant, 'management-date-range@example.com');
    $included = managementBooking($tenant, 'BR-MGMT-IN-RANGE', 'Included Customer', '01000000876');
    $excluded = managementBooking($tenant, 'BR-MGMT-OUT-RANGE', 'Excluded Customer', '01000000875');

    $included->update([
        'starts_at' => CarbonImmutable::parse('2026-10-02 23:45', 'Africa/Cairo')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-10-03 00:15', 'Africa/Cairo')->utc(),
        'block_ends_at' => CarbonImmutable::parse('2026-10-03 00:15', 'Africa/Cairo')->utc(),
    ]);
    $excluded->update([
        'starts_at' => CarbonImmutable::parse('2026-10-03 00:15', 'Africa/Cairo')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-10-03 00:45', 'Africa/Cairo')->utc(),
        'block_ends_at' => CarbonImmutable::parse('2026-10-03 00:45', 'Africa/Cairo')->utc(),
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index', [
            'tenant' => $tenant->slug,
            'from_date' => '2026-10-02',
            'to_date' => '2026-10-02',
        ]))
        ->assertOk()
        ->assertSee($included->booking_reference)
        ->assertDontSee($excluded->booking_reference);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.index', [
            'tenant' => $tenant->slug,
            'from_date' => '2026-10-03',
            'to_date' => '2026-10-02',
        ]))
        ->assertSessionHasErrors('to_date');
});

test('booking management is tenant isolated', function (): void {
    $tenantA = managementTenant('management-a');
    $userA = managementUser($tenantA, 'management-a@example.com');
    $bookingA = managementBooking($tenantA, 'BR-MGMT-A');

    $tenantB = managementTenant('management-b');
    managementBooking($tenantB, 'BR-MGMT-B');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('booking.management.index', ['tenant' => $tenantA->slug]))
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
        ->get(route('booking.management.show', ['tenant' => $tenantA->slug, 'booking' => $bookingB]))
        ->assertNotFound();

    expect($bookingA->exists)->toBeTrue();
});

test('authorized user can confirm and history is recorded', function (): void {
    $tenant = managementTenant('management-status');
    $user = managementUser($tenant, 'management-status@example.com');
    $booking = managementBooking($tenant, 'BR-MGMT-STATUS');

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('booking.management.status', ['tenant' => $tenant->slug, 'booking' => $booking]), [
            'status' => 'confirmed',
        ])
        ->assertRedirect(route('booking.management.show', ['tenant' => $tenant->slug, 'booking' => $booking]));

    app(CurrentTenant::class)->set($tenant);

    $fresh = $booking->fresh();

    expect($fresh?->status)->toBe(BookingStatus::Confirmed)
        ->and($fresh?->statusHistory()->count())->toBe(1);
});

test('booking detail preserves the service terms recorded at booking time', function (): void {
    $tenant = managementTenant('management-snapshot');
    $user = managementUser($tenant, 'management-snapshot@example.com');
    $booking = managementBooking($tenant, 'BR-MGMT-SNAPSHOT');

    app(CurrentTenant::class)->set($tenant);
    $service = Service::query()->findOrFail($booking->service_id);
    $service->update([
        'name' => ['en' => 'New Service Name', 'ar' => 'خدمة جديدة'],
        'price_minor' => 99000,
        'duration_minutes' => 90,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('booking.management.show', ['tenant' => $tenant->slug, 'booking' => $booking]))
        ->assertOk()
        ->assertSee('Consultation')
        ->assertSee('250.00 EGP')
        ->assertSee('30 '.__('app.booking_ui.minutes'))
        ->assertDontSee('New Service Name')
        ->assertDontSee('990.00 EGP');
});

test('booking filters reject service and staff IDs from another workspace', function (): void {
    $tenantA = managementTenant('management-filter-scope-a');
    $userA = managementUser($tenantA, 'management-filter-scope-a@example.com');

    $tenantB = managementTenant('management-filter-scope-b');
    $foreignBooking = managementBooking($tenantB, 'BR-MGMT-FOREIGN-FILTER');

    $foreignStaffUser = User::factory()->create(['email' => 'management-foreign-staff@example.com']);
    $foreignStaff = StaffProfile::query()->create([
        'user_id' => $foreignStaffUser->id,
        'display_name' => 'Foreign Workspace Staff',
        'status' => StaffStatus::Active,
    ]);

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('booking.management.index', [
            'tenant' => $tenantA->slug,
            'service_id' => $foreignBooking->service_id,
        ]))
        ->assertSessionHasErrors('service_id');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('booking.management.index', [
            'tenant' => $tenantA->slug,
            'staff_id' => $foreignStaff->getKey(),
        ]))
        ->assertSessionHasErrors('staff_id');
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
        ->get(route('booking.management.index', ['tenant' => $tenant->slug]))
        ->assertForbidden();
});
