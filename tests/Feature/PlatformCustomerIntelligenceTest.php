<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Models\Payment;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Service\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
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

function customerIntelligenceAdmin(): User
{
    $user = User::factory()->create();

    PlatformAdmin::query()->create([
        'user_id' => $user->id,
        'is_active' => true,
    ]);

    return $user;
}

function intelligenceTenant(User $owner, string $name): Tenant
{
    return app(CreateBusiness::class)->handle(
        $owner,
        BusinessType::query()->where('slug', 'clinic')->firstOrFail(),
        ['name' => $name],
    );
}

function intelligenceBooking(Tenant $tenant, Customer $customer, int $index): Booking
{
    return app(CurrentTenant::class)->run($tenant, function () use ($tenant, $customer, $index): Booking {
        $service = Service::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => ['en' => 'Consultation'],
            ],
            [
                'description' => ['en' => null, 'ar' => null],
                'price_minor' => 10000,
                'currency' => 'EGP',
                'duration_minutes' => 30,
                'buffer_minutes' => 0,
                'is_active' => true,
            ],
        );

        return Booking::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'staff_id' => null,
            'starts_at' => CarbonImmutable::now()->addDays($index + 1),
            'ends_at' => CarbonImmutable::now()->addDays($index + 1)->addMinutes(30),
            'block_ends_at' => CarbonImmutable::now()->addDays($index + 1)->addMinutes(30),
            'status' => BookingStatus::Completed,
            'payment_status' => BookingPaymentStatus::Paid,
            'booking_reference' => 'BR-INTEL'.$index,
        ]);
    });
}

function intelligencePayment(Tenant $tenant, Booking $booking, int $amountMinor, string $reference): void
{
    Payment::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => Booking::class,
        'payable_id' => $booking->id,
        'reference' => $reference,
        'provider' => 'kashier',
        'amount_minor' => $amountMinor,
        'currency' => 'EGP',
        'status' => \App\Domain\Payment\Enums\PaymentStatus::Paid,
        'method' => 'card',
        'paid_at' => now(),
    ]);
}

test('platform customer intelligence aggregates spending per customer and sorts highest first', function (): void {
    $admin = customerIntelligenceAdmin();
    $owner = User::factory()->create();
    $tenant = intelligenceTenant($owner, 'Intelligence Clinic');

    $vip = Customer::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'name' => 'High Value Customer',
        'phone' => '+201000000010',
        'email' => 'high@example.com',
        'is_vip' => true,
    ]);
    $regular = Customer::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Regular Customer',
        'phone' => '+201000000011',
        'email' => 'regular@example.com',
    ]);

    $vipBooking = intelligenceBooking($tenant, $vip, 1);
    $regularBooking = intelligenceBooking($tenant, $regular, 2);

    intelligencePayment($tenant, $vipBooking, 75000, 'PAY-INTEL-1');
    intelligencePayment($tenant, $regularBooking, 12000, 'PAY-INTEL-2');
    Payment::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => Booking::class,
        'payable_id' => $vipBooking->id,
        'reference' => 'PAY-INTEL-USD',
        'provider' => 'kashier',
        'amount_minor' => 5000,
        'currency' => 'USD',
        'status' => \\App\\Domain\\Payment\\Enums\\PaymentStatus::Paid,
        'method' => 'card',
        'paid_at' => now(),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.customers.index', ['tenant_id' => $tenant->id, 'currency' => 'EGP']));

    $response->assertOk()
        ->assertSee('High Value Customer')
        ->assertSee('Regular Customer')
        ->assertViewHas('customers', function ($customers): bool {
            $rows = $customers->getCollection();

            return $rows->first()->name === 'High Value Customer'
                && (int) $rows->first()->total_paid_minor === 75000;
        });

    $response->assertSee('750.00 EGP');

    $this->actingAs($admin)
        ->get(route('admin.customers.index', ['tenant_id' => $tenant->id, 'currency' => 'USD']))
        ->assertOk()
        ->assertSee('High Value Customer')
        ->assertSee('50.00 USD');

});

test('platform admin can view customer details and toggle vip without crossing tenants', function (): void {
    $admin = customerIntelligenceAdmin();
    $owner = User::factory()->create();
    $tenant = intelligenceTenant($owner, 'Customer Detail Clinic');

    $customer = Customer::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Detail Customer',
        'phone' => '+201000000020',
        'email' => 'detail@example.com',
    ]);

    $booking = intelligenceBooking($tenant, $customer, 3);
    intelligencePayment($tenant, $booking, 25000, 'PAY-INTEL-3');

    $this->actingAs($admin)
        ->get(route('admin.customers.show', [$tenant, $customer]))
        ->assertOk()
        ->assertSee('Detail Customer')
        ->assertSee('250.00 EGP')
        ->assertSee('BR-INTEL3');

    $otherTenant = intelligenceTenant(User::factory()->create(), 'Other Clinic');
    $otherCustomer = Customer::withoutGlobalScopes()->create([
        'tenant_id' => $otherTenant->id,
        'name' => 'Other Customer',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.customers.vip-toggle', [$tenant, $otherCustomer]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->patch(route('admin.customers.vip-toggle', [$tenant, $customer]))
        ->assertRedirect();

    expect($customer->fresh()->is_vip)->toBeTrue();
});

test('non platform admin cannot access customer intelligence', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.customers.index'))
        ->assertForbidden();
});
