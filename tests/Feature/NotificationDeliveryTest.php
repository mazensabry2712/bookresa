<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Jobs\SendBillingNotificationsForSubscription;
use App\Notifications\BookingNotification;
use App\Notifications\PaymentNotification;
use App\Notifications\SubscriptionExpiryNotification;
use App\Notifications\UsageWarningNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function notificationTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    \App\Domain\Business\Models\BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

function notificationService(): \App\Domain\Service\Models\Service
{
    return app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation', 'ar' => 'استشارة'],
        'price_minor' => 25000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);
}

function notificationUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    return $user;
}

function notificationBooking(Tenant $tenant, string $reference = 'BR-NOTIFY-001'): Booking
{
    app(CurrentTenant::class)->set($tenant);

    $customer = Customer::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Ahmed',
        'phone' => '01000000111',
        'normalized_phone' => '201000000111',
        'email' => 'ahmed@example.com',
    ]);

    $service = notificationService();
    $start = CarbonImmutable::now('UTC')->addHours(25)->startOfHour();

    return Booking::query()->create([
        'tenant_id' => $tenant->id,
        'customer_id' => $customer->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => $start,
        'ends_at' => $start->addMinutes(30),
        'block_ends_at' => $start->addMinutes(30),
        'status' => BookingStatus::Pending,
        'payment_status' => 'unpaid',
        'booking_reference' => $reference,
    ])->fresh(['customer', 'service']);
}

test('creating a booking queues a bilingual booking receipt', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-created');
    $service = notificationService();

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Tuesday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Wednesday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Thursday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Friday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Saturday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
        ['day_of_week' => DayOfWeek::Sunday->value, 'opens_at' => '00:00', 'closes_at' => '23:59'],
    ]);

    $booking = app(CreateBooking::class)->handle(
        $service,
        'Ahmed',
        '01000000111',
        'ahmed@example.com',
        CarbonImmutable::now('Africa/Cairo')->addDay()->addHour()->startOfHour(),
    );

    Notification::assertSentTo($booking->customer, BookingNotification::class, function (BookingNotification $notification) use ($booking): bool {
        $data = $notification->toArray($booking->customer);

        return $data['type'] === 'booking_created'
            && $data['title']['en'] === 'Booking received'
            && $data['title']['ar'] === 'تم استلام الحجز';
    });
});

test('booking confirmation and cancellation queue lifecycle notifications', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-lifecycle');
    $booking = notificationBooking($tenant, 'BR-NOTIFY-LIFECYCLE');

    app(UpdateBookingStatus::class)->handle($booking, BookingStatus::Confirmed);
    app(UpdateBookingStatus::class)->handle($booking->fresh(), BookingStatus::Cancelled);

    Notification::assertSentTo($booking->customer, BookingNotification::class, function (BookingNotification $notification) use ($booking): bool {
        return in_array(
            $notification->toArray($booking->customer)['type'],
            ['booking_confirmed', 'booking_cancelled'],
            true,
        );
    });
});

test('paid and failed booking payments notify the customer', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-payment');
    $booking = notificationBooking($tenant, 'BR-NOTIFY-PAYMENT');

    $payment = Payment::query()->create([
        'tenant_id' => $tenant->id,
        'payable_type' => $booking->getMorphClass(),
        'payable_id' => $booking->id,
        'reference' => 'PAY-NOTIFY-001',
        'provider' => 'kashier',
        'amount_minor' => 25000,
        'currency' => 'EGP',
        'status' => PaymentStatus::Pending,
    ]);

    $paid = app(PaymentService::class)->applyResult($payment, new PaymentGatewayResult(
        status: PaymentStatus::Paid,
        providerReference: 'KASH-001',
        paidAt: CarbonImmutable::now('UTC'),
    ));

    Notification::assertSentTo($booking->customer, PaymentNotification::class, function (PaymentNotification $notification) use ($booking): bool {
        return $notification->toArray($booking->customer)['type'] === 'payment_paid';
    });

    Notification::fake();

    $failed = Payment::query()->findOrFail($paid->id);
    $failed->forceFill(['status' => PaymentStatus::Pending])->save();
    app(PaymentService::class)->applyResult($failed, new PaymentGatewayResult(
        status: PaymentStatus::Failed,
    ));

    Notification::assertSentTo($booking->customer, PaymentNotification::class, function (PaymentNotification $notification) use ($booking): bool {
        return $notification->toArray($booking->customer)['type'] === 'payment_failed';
    });
});

test('booking reminder command is idempotent for the same booking', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-reminder');
    $booking = notificationBooking($tenant, 'BR-NOTIFY-REMINDER');
    $booking->forceFill([
        'status' => BookingStatus::Confirmed,
        'starts_at' => CarbonImmutable::now('UTC')->addHours(24),
        'ends_at' => CarbonImmutable::now('UTC')->addHours(24)->addMinutes(30),
        'block_ends_at' => CarbonImmutable::now('UTC')->addHours(24)->addMinutes(30),
        'reminder_sent_at' => null,
    ])->save();

    expect(Artisan::call('bookresa:send-booking-reminders', [
        '--hours' => 24,
        '--window' => 60,
    ]))->toBe(0);

    $booking->refresh();
    expect($booking->reminder_sent_at)->not->toBeNull();

    expect(Artisan::call('bookresa:send-booking-reminders', [
        '--hours' => 24,
        '--window' => 60,
    ]))->toBe(0);

    app(CurrentTenant::class)->set($tenant);
    $booking->load('customer');

    Notification::assertSentTo($booking->customer, BookingNotification::class, function (BookingNotification $notification) use ($booking): bool {
        return $notification->toArray($booking->customer)['type'] === 'booking_reminder';
    });
});

test('billing notification command dispatches one job per active subscription', function (): void {
    Queue::fake();

    $tenant = notificationTenant('notify-dispatch');
    $plan = Plan::query()->create([
        'name' => ['en' => 'Starter', 'ar' => 'البداية'],
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'start_at' => CarbonImmutable::now('UTC'),
        'end_at' => CarbonImmutable::now('UTC')->addMonth(),
        'status' => SubscriptionStatus::Active,
        'payment_status' => PaymentStatus::Paid,
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'pricing_snapshot' => [],
    ]);

    expect(Artisan::call('bookresa:send-billing-notifications'))->toBe(0);

    Queue::assertPushed(
        SendBillingNotificationsForSubscription::class,
        fn (SendBillingNotificationsForSubscription $job): bool => true,
    );

    expect($subscription->exists)->toBeTrue();
});

test('billing notification command warns the workspace owner about expiry', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-expiry');
    $owner = notificationUser($tenant, 'owner-expiry@example.com');

    $plan = Plan::query()->create([
        'name' => ['en' => 'Starter', 'ar' => 'البداية'],
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'start_at' => CarbonImmutable::now('UTC')->subMonth(),
        'end_at' => CarbonImmutable::now('UTC')->addHours(12),
        'status' => SubscriptionStatus::Active,
        'payment_status' => PaymentStatus::Paid,
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 10,
        'additional_customer_price_minor' => 1000,
        'pricing_snapshot' => [],
    ]);

    expect(Artisan::call('bookresa:send-billing-notifications'))->toBe(0);

    Notification::assertSentTo($owner, SubscriptionExpiryNotification::class);
});

test('billing notification command warns at customer usage threshold', function (): void {
    Notification::fake();

    $tenant = notificationTenant('notify-usage');
    $owner = notificationUser($tenant, 'owner-usage@example.com');

    $plan = Plan::query()->create([
        'name' => ['en' => 'Starter', 'ar' => 'البداية'],
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 5,
        'additional_customer_price_minor' => 1000,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = Subscription::query()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'start_at' => CarbonImmutable::now('UTC')->subMonth(),
        'end_at' => CarbonImmutable::now('UTC')->addMonth(),
        'status' => SubscriptionStatus::Active,
        'payment_status' => PaymentStatus::Paid,
        'price_minor' => 50000,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 5,
        'additional_customer_price_minor' => 1000,
        'pricing_snapshot' => [],
    ]);

    foreach (range(1, 4) as $index) {
        Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Customer '.$index,
            'phone' => '01000000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'normalized_phone' => '201000000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
        ]);
    }

    expect(Artisan::call('bookresa:send-billing-notifications', [
        '--usage-threshold' => 80,
    ]))->toBe(0);

    Notification::assertSentTo($owner, UsageWarningNotification::class, function (UsageWarningNotification $notification) use ($owner): bool {
        $data = $notification->toArray($owner);

        return $data['type'] === 'usage_warning'
            && $data['customer_count'] === 4
            && $data['included_customer_limit'] === 5;
    });
});
