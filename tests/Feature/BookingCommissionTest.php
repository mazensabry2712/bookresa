<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\BookingCommissionPolicy;
use App\Domain\Payment\Services\SyncBookingCommission;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function commissionTestTenant(string $slug = 'commission-workspace'): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Commission clinic', 'ar' => 'عيادة العمولة'],
        'timezone' => 'Africa/Cairo',
    ]);

    app(PlatformSettings::class)->put([
        'centralized_booking_payments_enabled' => '0',
        'commission_free_customer_limit' => '2',
        'booking_commission_rate_basis_points' => '500',
    ]);

    return $tenant;
}

function commissionTestCustomer(string $name, int $sequence): Customer
{
    return Customer::query()->create([
        'name' => $name,
        'phone' => sprintf('0101000%04d', $sequence),
        'normalized_phone' => sprintf('20101000%04d', $sequence),
    ]);
}

function commissionTestBooking(Tenant $tenant, Customer $customer): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 20000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addMinutes(30),
        'block_ends_at' => now()->addDays(2)->addMinutes(30),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => 'BR-COMMISSION-'.$customer->getKey(),
    ]);
}

test('customer profiles over the included limit receive the default five percent commission policy', function (): void {
    $tenant = commissionTestTenant();
    $firstCustomer = commissionTestCustomer('Customer 1', 1);
    commissionTestCustomer('Customer 2', 2);
    $thirdCustomer = commissionTestCustomer('Customer 3', 3);

    $firstSnapshot = app(BookingCommissionPolicy::class)->snapshot(commissionTestBooking($tenant, $firstCustomer));
    $eligibleSnapshot = app(BookingCommissionPolicy::class)->snapshot(commissionTestBooking($tenant, $thirdCustomer));

    expect($firstSnapshot['customer_ordinal'])->toBe(1)
        ->and($firstSnapshot['eligible'])->toBeFalse()
        ->and($eligibleSnapshot['customer_ordinal'])->toBe(3)
        ->and($eligibleSnapshot['included_customer_limit'])->toBe(2)
        ->and($eligibleSnapshot['eligible'])->toBeTrue()
        ->and($eligibleSnapshot['rate_basis_points'])->toBe(500);
});

test('successful booking payments accrue one commission row and refunds recalculate the workspace net', function (): void {
    $tenant = commissionTestTenant('commission-refund-workspace');
    commissionTestCustomer('Customer 1', 1);
    commissionTestCustomer('Customer 2', 2);
    $customer = commissionTestCustomer('Customer 3', 3);
    $booking = commissionTestBooking($tenant, $customer);
    $snapshot = app(BookingCommissionPolicy::class)->snapshot($booking);

    $payment = Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-COMMISSION-001',
        'provider' => 'fake',
        'amount_minor' => 20000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
        'metadata' => ['centralized_payment' => true, 'commission_snapshot' => $snapshot],
    ]);

    $first = app(SyncBookingCommission::class)->handle($payment);
    $duplicate = app(SyncBookingCommission::class)->handle($payment);

    expect($first?->commission_amount_minor)->toBe(1000)
        ->and($first?->workspace_net_minor)->toBe(19000)
        ->and($duplicate?->id)->toBe($first?->id)
        ->and(BookingCommission::query()->count())->toBe(1);

    $payment->forceFill([
        'refunded_amount_minor' => 5000,
        'metadata' => array_merge($payment->metadata ?? [], ['refunded_amount_minor' => 5000]),
    ])->save();

    $partialRefund = app(SyncBookingCommission::class)->handle($payment->fresh());

    expect($partialRefund?->commission_amount_minor)->toBe(750)
        ->and($partialRefund?->workspace_net_minor)->toBe(14250)
        ->and($partialRefund?->refunded_amount_minor)->toBe(5000);

    $payment->forceFill([
        'status' => PaymentStatus::Refunded,
        'refunded_amount_minor' => 20000,
        'metadata' => array_merge($payment->metadata ?? [], ['refunded_amount_minor' => 20000]),
    ])->save();

    $fullRefund = app(SyncBookingCommission::class)->handle($payment->fresh());

    expect($fullRefund?->status)->toBe('reversed')
        ->and($fullRefund?->commission_amount_minor)->toBe(0)
        ->and($fullRefund?->workspace_net_minor)->toBe(0)
        ->and(BookingCommission::query()->count())->toBe(1);
});
