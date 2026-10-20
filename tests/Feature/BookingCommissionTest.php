<?php

use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\BookingCommission;
use App\Domain\Payment\Models\BookingCommissionAllocation;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\SyncBookingCommission;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    Carbon::setTestNow();
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
        'booking_commission_enabled' => '1',
        'commission_free_booking_limit' => '1',
        'monthly_booking_commission_rate_basis_points' => '1000',
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

function commissionTestBooking(
    Tenant $tenant,
    Customer $customer,
    string $reference,
    int $amountMinor = 20000,
): Booking {
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => $amountMinor,
        'currency' => 'EGP',
        'duration_minutes' => 30,
    ]);

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'service_name_snapshot' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'service_price_minor_snapshot' => $amountMinor,
        'service_currency_snapshot' => 'EGP',
        'service_duration_minutes_snapshot' => 30,
        'service_buffer_minutes_snapshot' => 0,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addMinutes(30),
        'block_ends_at' => now()->addDays(2)->addMinutes(30),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

function confirmCommissionTestBooking(Booking $booking): BookingCommissionAllocation
{
    app(UpdateBookingStatus::class)->handle($booking, BookingStatus::Confirmed);

    return BookingCommissionAllocation::query()
        ->where('booking_id', $booking->getKey())
        ->firstOrFail();
}

function commissionTestPayment(Booking $booking, string $reference, int $amountMinor = 20000): Payment
{
    return Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => $reference,
        'provider' => 'fake',
        'amount_minor' => $amountMinor,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
        'metadata' => ['centralized_payment' => true],
    ]);
}

test('booking ordinals 1000 and 1001 respect the free monthly allowance and ten percent rate', function (): void {
    Notification::fake();
    $tenant = commissionTestTenant('monthly-commission-boundary');
    app(PlatformSettings::class)->put([
        'booking_commission_enabled' => '1',
        'commission_free_booking_limit' => '1000',
        'monthly_booking_commission_rate_basis_points' => '1000',
    ]);
    $customer = commissionTestCustomer('Repeat Customer', 1);
    $periodStart = CarbonImmutable::now('Africa/Cairo')->startOfMonth()->toDateString();

    // Seed the exact counter boundary instead of creating one thousand unrelated fixtures.
    DB::table('booking_commission_period_counters')->insert([
        'tenant_id' => $tenant->id,
        'period_start' => $periodStart,
        'next_ordinal' => 1000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $booking1000 = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-1000');
    $booking1001 = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-1001');
    $first = confirmCommissionTestBooking($booking1000);
    $second = confirmCommissionTestBooking($booking1001);

    expect($first->booking_ordinal)->toBe(1000)
        ->and($first->eligible)->toBeFalse()
        ->and($first->commission_amount_minor_snapshot)->toBe(0)
        ->and($second->booking_ordinal)->toBe(1001)
        ->and($second->eligible)->toBeTrue()
        ->and($second->rate_basis_points)->toBe(1000)
        ->and($second->booking_amount_minor_snapshot)->toBe(20000)
        ->and($second->commission_amount_minor_snapshot)->toBe(2000)
        ->and($first->booking_id)->not->toBe($second->booking_id);
});

test('each booking counts for a repeat customer and reschedule confirmation does not allocate again', function (): void {
    Notification::fake();
    $tenant = commissionTestTenant('monthly-commission-repeat-customer');
    $customer = commissionTestCustomer('Same Customer', 2);
    $firstBooking = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-REPEAT-1');
    $secondBooking = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-REPEAT-2');

    $firstAllocation = confirmCommissionTestBooking($firstBooking);
    $secondAllocation = confirmCommissionTestBooking($secondBooking);

    app(UpdateBookingStatus::class)->handle($secondBooking->fresh(), BookingStatus::Rescheduled);
    app(UpdateBookingStatus::class)->handle($secondBooking->fresh(), BookingStatus::Confirmed);
    $secondAfterReschedule = BookingCommissionAllocation::query()
        ->where('booking_id', $secondBooking->id)
        ->firstOrFail();

    expect($firstAllocation->booking_ordinal)->toBe(1)
        ->and($firstAllocation->eligible)->toBeFalse()
        ->and($secondAllocation->booking_ordinal)->toBe(2)
        ->and($secondAllocation->eligible)->toBeTrue()
        ->and($secondAfterReschedule->id)->toBe($secondAllocation->id)
        ->and($secondAfterReschedule->booking_ordinal)->toBe(2)
        ->and(BookingCommissionAllocation::query()->count())->toBe(2);
});

test('monthly counters reset in the workspace timezone and remain tenant scoped', function (): void {
    Notification::fake();
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-31 23:30:00', 'Africa/Cairo'));
    $firstTenant = commissionTestTenant('monthly-commission-tenant-a');
    $customerA = commissionTestCustomer('Customer A', 3);
    $octoberBooking = commissionTestBooking($firstTenant, $customerA, 'BR-COMMISSION-OCT');
    $octoberAllocation = confirmCommissionTestBooking($octoberBooking);

    $secondTenant = commissionTestTenant('monthly-commission-tenant-b');
    $customerB = commissionTestCustomer('Customer B', 4);
    $otherWorkspaceBooking = commissionTestBooking($secondTenant, $customerB, 'BR-COMMISSION-TENANT-B');
    $otherWorkspaceAllocation = confirmCommissionTestBooking($otherWorkspaceBooking);

    Carbon::setTestNow(CarbonImmutable::parse('2026-11-01 00:30:00', 'Africa/Cairo'));
    app(CurrentTenant::class)->set($firstTenant);
    $novemberCustomer = commissionTestCustomer('Customer November', 5);
    $novemberBooking = commissionTestBooking($firstTenant, $novemberCustomer, 'BR-COMMISSION-NOV');
    $novemberAllocation = confirmCommissionTestBooking($novemberBooking);

    expect($octoberAllocation->period_start->toDateString())->toBe('2026-10-01')
        ->and($octoberAllocation->booking_ordinal)->toBe(1)
        ->and($otherWorkspaceAllocation->period_start->toDateString())->toBe('2026-10-01')
        ->and($otherWorkspaceAllocation->booking_ordinal)->toBe(1)
        ->and($novemberAllocation->period_start->toDateString())->toBe('2026-11-01')
        ->and($novemberAllocation->booking_ordinal)->toBe(1)
        ->and(BookingCommissionAllocation::withoutGlobalScopes()->count())->toBe(3);
});

test('online payment commission reconciliation is capped per booking across retries and refunds', function (): void {
    Notification::fake();
    $tenant = commissionTestTenant('monthly-commission-reconcile');
    app(PlatformSettings::class)->put([
        'booking_commission_enabled' => '1',
        'commission_free_booking_limit' => '0',
        'monthly_booking_commission_rate_basis_points' => '1000',
    ]);
    $customer = commissionTestCustomer('Paying Customer', 6);
    $booking = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-PAID');
    $allocation = confirmCommissionTestBooking($booking);

    $firstPayment = commissionTestPayment($booking, 'PAY-COMMISSION-ATTEMPT-1');
    $secondPayment = commissionTestPayment($booking, 'PAY-COMMISSION-ATTEMPT-2');

    $first = app(SyncBookingCommission::class)->handle($firstPayment);
    $second = app(SyncBookingCommission::class)->handle($secondPayment);

    expect($first?->commission_amount_minor)->toBe(2000)
        ->and($second?->commission_amount_minor)->toBe(0)
        ->and(BookingCommission::query()->where('booking_commission_allocation_id', $allocation->id)->sum('commission_amount_minor'))->toBe(2000)
        ->and(BookingCommission::query()->where('booking_commission_allocation_id', $allocation->id)->count())->toBe(2);

    $firstPayment->forceFill([
        'refunded_amount_minor' => 5000,
        'metadata' => array_merge($firstPayment->metadata ?? [], ['refunded_amount_minor' => 5000]),
    ])->save();
    app(SyncBookingCommission::class)->handle($firstPayment->fresh());

    expect((int) BookingCommission::query()->where('booking_commission_allocation_id', $allocation->id)->sum('commission_amount_minor'))->toBe(2000)
        ->and(BookingCommission::query()->where('payment_id', $firstPayment->id)->value('commission_amount_minor'))->toBe(1500)
        ->and(BookingCommission::query()->where('payment_id', $secondPayment->id)->value('commission_amount_minor'))->toBe(500);

    $firstPayment->forceFill([
        'status' => PaymentStatus::Refunded,
        'refunded_amount_minor' => 20000,
    ])->save();
    app(SyncBookingCommission::class)->handle($firstPayment->fresh());

    expect((int) BookingCommission::query()->where('booking_commission_allocation_id', $allocation->id)->sum('commission_amount_minor'))->toBe(2000)
        ->and(BookingCommission::query()->where('payment_id', $firstPayment->id)->value('commission_amount_minor'))->toBe(0)
        ->and(BookingCommission::query()->where('payment_id', $secondPayment->id)->value('commission_amount_minor'))->toBe(2000);

    $secondPayment->forceFill([
        'status' => PaymentStatus::Refunded,
        'refunded_amount_minor' => 20000,
    ])->save();
    app(SyncBookingCommission::class)->handle($secondPayment->fresh());

    expect((int) BookingCommission::query()->where('booking_commission_allocation_id', $allocation->id)->sum('commission_amount_minor'))->toBe(0)
        ->and(BookingCommission::query()->where('payment_id', $secondPayment->id)->value('status'))->toBe('reversed');
});

test('the commission policy can be disabled without disabling bookings or allocating a commission', function (): void {
    Notification::fake();
    $tenant = commissionTestTenant('monthly-commission-disabled');
    app(PlatformSettings::class)->put([
        'booking_commission_enabled' => '0',
        'commission_free_booking_limit' => '1',
        'monthly_booking_commission_rate_basis_points' => '1000',
    ]);
    $customer = commissionTestCustomer('Disabled Commission Customer', 7);
    $booking = commissionTestBooking($tenant, $customer, 'BR-COMMISSION-DISABLED');
    $allocation = confirmCommissionTestBooking($booking);

    expect($allocation->booking_ordinal)->toBe(1)
        ->and($allocation->commission_enabled)->toBeFalse()
        ->and($allocation->eligible)->toBeFalse()
        ->and($allocation->commission_amount_minor_snapshot)->toBe(0);
});
