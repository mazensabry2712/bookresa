<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
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

function calendarTenant(string $slug): Tenant
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

function calendarUser(Tenant $tenant, string $email, bool $canView = true): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    if ($canView) {
        setPermissionsTeamId($tenant->id);
        Permission::firstOrCreate([
            'name' => 'calendar.view',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'calendar-viewer',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $role->syncPermissions(['calendar.view']);
        $user->assignRole($role);
    }

    return $user;
}

function calendarBooking(Tenant $tenant, string $reference, string $localStart): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Calendar Customer',
        'phone' => $reference,
        'normalized_phone' => preg_replace('/\D+/', '', $reference),
    ]);

    $start = CarbonImmutable::parse($localStart, 'Africa/Cairo');
    $end = $start->addMinutes(30);

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $start->utc(),
        'ends_at' => $end->utc(),
        'block_ends_at' => $end->utc(),
        'status' => BookingStatus::Confirmed,
        'payment_status' => 'paid',
        'booking_reference' => $reference,
    ]);
}

test('authorized user can open the monthly calendar', function (): void {
    $tenant = calendarTenant('calendar-open');
    $user = calendarUser($tenant, 'calendar-open@example.com');
    $booking = calendarBooking($tenant, 'BR-CAL-OPEN', '2026-09-28 10:00');

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee('BR-CAL-OPEN')
        ->assertSee('Calendar Customer')
        ->assertSee('10:00')
        ->assertSee('Consultation');
});

test('calendar filters by service and status', function (): void {
    $tenant = calendarTenant('calendar-filter');
    $user = calendarUser($tenant, 'calendar-filter@example.com');
    $booking = calendarBooking($tenant, 'BR-CAL-FILTER', '2026-09-28 10:00');

    app(CurrentTenant::class)->set($tenant);
    $service = Service::query()->firstOrFail();

    $other = calendarBooking($tenant, 'BR-CAL-OTHER', '2026-09-29 11:00');
    $other->update(['status' => BookingStatus::Cancelled]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index', [
            'month' => '2026-09',
            'service_id' => $service->id,
            'status' => 'confirmed',
        ]))
        ->assertOk()
        ->assertSee($booking->booking_reference)
        ->assertDontSee($other->booking_reference);
});

test('calendar is tenant isolated', function (): void {
    $tenantA = calendarTenant('calendar-a');
    $userA = calendarUser($tenantA, 'calendar-a@example.com');
    $bookingA = calendarBooking($tenantA, 'BR-CAL-A', '2026-09-28 10:00');

    $tenantB = calendarTenant('calendar-b');
    $bookingB = calendarBooking($tenantB, 'BR-CAL-B', '2026-09-28 11:00');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->get(route('calendar.index', ['month' => '2026-09']))
        ->assertOk()
        ->assertSee($bookingA->booking_reference)
        ->assertDontSee($bookingB->booking_reference);
});

test('calendar requires calendar permission', function (): void {
    $tenant = calendarTenant('calendar-forbidden');
    $user = calendarUser($tenant, 'calendar-forbidden@example.com', false);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('calendar.index', ['month' => '2026-09']))
        ->assertForbidden();
});
