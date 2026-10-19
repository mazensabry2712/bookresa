<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Models\WorkspacePayout;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

test('platform finance admin can reserve and reconcile a payout without exceeding workspace balance', function (): void {
    $tenant = Tenant::query()->create([
        'slug' => 'payout-workspace',
        'status' => TenantStatus::Active,
    ]);
    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Payout Clinic', 'ar' => 'عيادة الصرف'],
        'timezone' => 'Africa/Cairo',
    ]);
    $customer = Customer::query()->create([
        'name' => 'Payout Customer',
        'phone' => '01010002000',
        'normalized_phone' => '201010002000',
    ]);
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);
    $booking = Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => CarbonImmutable::parse('2026-10-25 10:00:00', 'Africa/Cairo')->utc(),
        'ends_at' => CarbonImmutable::parse('2026-10-25 10:30:00', 'Africa/Cairo')->utc(),
        'block_ends_at' => CarbonImmutable::parse('2026-10-25 10:30:00', 'Africa/Cairo')->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => 'BR-PAYOUT-0001',
    ]);
    $payment = Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-PAYOUT-0001',
        'provider' => 'fake',
        'amount_minor' => 10000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
        'metadata' => [],
    ]);
    BookingCommission::query()->create([
        'payment_id' => $payment->id,
        'customer_id' => $customer->id,
        'customer_ordinal' => 1001,
        'included_customer_limit' => 1000,
        'eligible' => true,
        'rate_basis_points' => 500,
        'gross_amount_minor' => 10000,
        'refunded_amount_minor' => 0,
        'commission_amount_minor' => 500,
        'workspace_net_minor' => 9500,
        'currency' => 'EGP',
        'status' => 'accrued',
        'metadata' => [],
    ]);

    $admin = User::factory()->create(['email' => 'payout-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);
    app(CurrentTenant::class)->clear();

    $this->actingAs($admin)
        ->get(route('admin.commissions.index'))
        ->assertOk()
        ->assertSee('Payout Clinic')
        ->assertSee('Booking commissions & settlements');

    $this->actingAs($admin)
        ->post(route('admin.commissions.payouts.store'), [
            'tenant_id' => $tenant->id,
            'currency' => 'EGP',
            'amount' => '90.00',
            'payout_method' => 'Bank transfer',
            'notes' => 'October payout',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Payout reserved successfully.');

    $payout = WorkspacePayout::query()->where('tenant_id', $tenant->id)->firstOrFail();

    expect($payout->amount_minor)->toBe(9000)
        ->and($payout->status)->toBe('pending');

    $this->actingAs($admin)
        ->post(route('admin.commissions.payouts.store'), [
            'tenant_id' => $tenant->id,
            'currency' => 'EGP',
            'amount' => '10.00',
            'payout_method' => 'Bank transfer',
        ])
        ->assertSessionHasErrors('amount');

    $this->actingAs($admin)
        ->patch(route('admin.commissions.payouts.paid', $payout), [
            'payout_reference' => 'BANK-TRANSFER-REF-123',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Payout marked as paid.');

    expect($payout->fresh()->status)->toBe('paid')
        ->and($payout->fresh()->payout_reference)->toBe('BANK-TRANSFER-REF-123');
});
