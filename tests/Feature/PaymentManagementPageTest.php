<?php

use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Middleware\EnsureTenantModuleEnabled;
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

function paymentPageTenant(string $slug): Tenant
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

function paymentPageUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    setPermissionsTeamId($tenant->id);

    Permission::firstOrCreate([
        'name' => 'billing.view',
        'guard_name' => 'web',
    ]);

    $role = Role::firstOrCreate([
        'name' => 'payment-page-viewer',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);
    $role->syncPermissions(['billing.view']);
    $user->assignRole($role);

    return $user;
}

function paymentPageBooking(Tenant $tenant, string $reference, string $customerName): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $phone = '010'.str_pad((string) (abs(crc32($reference)) % 100000000), 8, '0', STR_PAD_LEFT);

    $customer = Customer::query()->create([
        'name' => $customerName,
        'phone' => $phone,
        'normalized_phone' => preg_replace('/\D+/', '', $phone),
        'email' => strtolower(str_replace(' ', '.', $customerName)).'@example.test',
    ]);

    $start = CarbonImmutable::parse('2026-10-14 10:00', 'Africa/Cairo');

    return Booking::query()->create([
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'starts_at' => $start->utc(),
        'ends_at' => $start->addMinutes(30)->utc(),
        'block_ends_at' => $start->addMinutes(30)->utc(),
        'status' => BookingStatus::Confirmed,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ]);
}

function paymentPagePayment(
    Tenant $tenant,
    Booking $booking,
    string $reference,
    PaymentStatus $status = PaymentStatus::Paid,
    int $amountMinor = 25000,
    int $refundedAmountMinor = 0,
    ?string $paidAt = '2026-10-14 09:00:00',
    ?string $createdAt = '2026-10-14 08:00:00',
): Payment {
    app(CurrentTenant::class)->set($tenant);

    $payment = Payment::query()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => $reference,
        'provider' => 'kashier',
        'provider_reference' => 'provider-'.$reference,
        'amount_minor' => $amountMinor,
        'refunded_amount_minor' => $refundedAmountMinor,
        'currency' => 'EGP',
        'status' => $status,
        'paid_at' => $paidAt === null ? null : CarbonImmutable::parse($paidAt, 'UTC'),
    ]);

    if ($createdAt !== null) {
        $payment->forceFill([
            'created_at' => CarbonImmutable::parse($createdAt, 'UTC'),
            'updated_at' => CarbonImmutable::parse($createdAt, 'UTC'),
        ])->save();
    }

    return $payment->fresh();
}

test('workspace payment page is tenant-scoped and excludes subscription billing records', function (): void {
    $tenantA = paymentPageTenant('payments-page-a');
    $userA = paymentPageUser($tenantA, 'payments-page-a@example.test');
    $bookingA = paymentPageBooking($tenantA, 'BR-PAGE-A', 'Local Customer');
    paymentPagePayment($tenantA, $bookingA, 'PAY-PAGE-LOCAL');

    Payment::query()->create([
        'tenant_id' => $tenantA->id,
        'payable_type' => (new Subscription())->getMorphClass(),
        'payable_id' => 999,
        'reference' => 'PAY-PAGE-SUBSCRIPTION',
        'provider' => 'kashier',
        'amount_minor' => 19900,
        'refunded_amount_minor' => 0,
        'currency' => 'EGP',
        'status' => PaymentStatus::Paid,
        'paid_at' => CarbonImmutable::parse('2026-10-14 10:00:00', 'UTC'),
    ]);

    $tenantB = paymentPageTenant('payments-page-b');
    $bookingB = paymentPageBooking($tenantB, 'BR-PAGE-B', 'Foreign Customer');
    paymentPagePayment($tenantB, $bookingB, 'PAY-PAGE-FOREIGN');

    $this->actingAs($userA)
        ->withSession(['tenant_id' => $tenantA->id])
        ->withoutMiddleware(EnsureTenantModuleEnabled::class)
        ->get(route('payments.index', ['tenant' => $tenantA->slug]))
        ->assertOk()
        ->assertSee('PAY-PAGE-LOCAL')
        ->assertSee('Local Customer')
        ->assertDontSee('PAY-PAGE-SUBSCRIPTION')
        ->assertDontSee('PAY-PAGE-FOREIGN')
        ->assertDontSee('Foreign Customer');
});

test('workspace payments support customer search, status and payment-date filters', function (): void {
    $tenant = paymentPageTenant('payments-page-filter');
    $user = paymentPageUser($tenant, 'payments-page-filter@example.test');

    $paidBooking = paymentPageBooking($tenant, 'BR-PAGE-SEARCH-PAID', 'Samira Local');
    paymentPagePayment(
        $tenant,
        $paidBooking,
        'PAY-PAGE-SEARCH-PAID',
        PaymentStatus::Paid,
        25000,
        5000,
        '2026-10-14 09:00:00',
        '2026-10-13 08:00:00',
    );

    $pendingBooking = paymentPageBooking($tenant, 'BR-PAGE-SEARCH-PENDING', 'Other Local');
    paymentPagePayment(
        $tenant,
        $pendingBooking,
        'PAY-PAGE-SEARCH-PENDING',
        PaymentStatus::Pending,
        15000,
        0,
        null,
        '2026-10-14 08:00:00',
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->withoutMiddleware(EnsureTenantModuleEnabled::class)
        ->get(route('payments.index', [
            'tenant' => $tenant->slug,
            'q' => 'Samira',
            'status' => PaymentStatus::Paid->value,
            'from' => '2026-10-14',
            'to' => '2026-10-14',
        ]))
        ->assertOk()
        ->assertSee('PAY-PAGE-SEARCH-PAID')
        ->assertSee('Samira Local')
        ->assertDontSee('PAY-PAGE-SEARCH-PENDING')
        ->assertViewHas('recordCount', 1)
        ->assertViewHas('netReceivedCurrencies', function ($currencies): bool {
            return $currencies->contains(
                fn ($currency): bool => $currency->currency === 'EGP'
                    && (int) $currency->net_received_minor === 20000,
            );
        });
});

test('workspace payment summary counts pending payments and does not add unlike currencies together', function (): void {
    $tenant = paymentPageTenant('payments-page-summary');
    $user = paymentPageUser($tenant, 'payments-page-summary@example.test');

    $paidBooking = paymentPageBooking($tenant, 'BR-PAGE-SUMMARY-PAID', 'Paid Customer');
    paymentPagePayment(
        $tenant,
        $paidBooking,
        'PAY-PAGE-SUMMARY-PAID',
        PaymentStatus::Paid,
        25000,
        5000,
    );

    $pendingBooking = paymentPageBooking($tenant, 'BR-PAGE-SUMMARY-PENDING', 'Pending Customer');
    paymentPagePayment(
        $tenant,
        $pendingBooking,
        'PAY-PAGE-SUMMARY-PENDING',
        PaymentStatus::Pending,
        15000,
        0,
        null,
        '2026-10-14 12:00:00',
    );

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->withoutMiddleware(EnsureTenantModuleEnabled::class)
        ->get(route('payments.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('200.00 EGP')
        ->assertViewHas('recordCount', 2)
        ->assertViewHas('awaitingCount', 1);
});
