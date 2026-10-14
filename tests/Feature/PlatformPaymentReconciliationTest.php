<?php

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus as BookingPaymentStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Enums\PaymentStatus as PaymentRecordStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakePaymentGateway;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

test('platform payment verification and refund synchronize booking payment status', function (): void {
    $tenant = Tenant::query()->create([
        'slug' => 'payment-reconciliation-business',
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => 'Payment Reconciliation', 'ar' => 'تسوية المدفوعات'],
        'email' => 'billing@example.com',
        'timezone' => 'Africa/Cairo',
    ]);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $customer = Customer::query()->create([
        'name' => 'Payment Customer',
        'phone' => '01000000888',
        'normalized_phone' => '201000000888',
    ]);

    $startsAt = CarbonImmutable::parse('2026-10-20 10:00:00', 'Africa/Cairo');
    $booking = Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $startsAt->utc(),
        'ends_at' => $startsAt->addMinutes(30)->utc(),
        'block_ends_at' => $startsAt->addMinutes(30)->utc(),
        'status' => BookingStatus::Pending,
        'payment_status' => BookingPaymentStatus::Unpaid,
        'booking_reference' => 'BR-PAYMENT-RECON-001',
    ]);

    $payment = Payment::query()->create([
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-RECON-001',
        'provider' => 'kashier',
        'provider_reference' => 'FAKE-PAYMENT-RECON-001',
        'amount_minor' => 25000,
        'currency' => 'EGP',
        'status' => PaymentRecordStatus::Processing,
        'metadata' => [
            'connected_account_merchant_id' => 'MID-RECONCILIATION',
        ],
    ]);

    app(CurrentTenant::class)->clear();

    $admin = User::factory()->create([
        'name' => 'Payment Reconciliation Admin',
        'email' => 'payment-reconciliation-admin@example.com',
    ]);

    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
        'role' => 'super_admin',
    ]);

    $this->app->instance(PaymentGateway::class, new FakePaymentGateway);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', ['paymentId' => $payment->id]))
        ->assertRedirect();

    $verifiedPayment = Payment::withoutGlobalScopes()->findOrFail($payment->id);
    $verifiedBooking = Booking::withoutGlobalScopes()->findOrFail($booking->id);

    expect($verifiedPayment->status)->toBe(PaymentRecordStatus::Paid)
        ->and($verifiedBooking->payment_status)->toBe(BookingPaymentStatus::Paid);

    $this->actingAs($admin)
        ->post(route('admin.payments.refund', ['paymentId' => $payment->id]))
        ->assertRedirect();

    $refundedPayment = Payment::withoutGlobalScopes()->findOrFail($payment->id);
    $refundedBooking = Booking::withoutGlobalScopes()->findOrFail($booking->id);

    expect($refundedPayment->status)->toBe(PaymentRecordStatus::Refunded)
        ->and($refundedBooking->payment_status)->toBe(BookingPaymentStatus::Refunded);
});
