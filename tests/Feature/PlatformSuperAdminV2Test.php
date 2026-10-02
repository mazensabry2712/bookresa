<?php

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Data\PaymentRequest;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Platform\Models\PlatformBroadcast;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Models\User;
use App\Notifications\PlatformBroadcastNotification;
use App\Notifications\SupportReplyNotification;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        Database\Seeders\ModuleSeeder::class,
        Database\Seeders\BusinessTypeSeeder::class,
    ]);
});

function v2Admin(string $role = 'super_admin', array $permissions = []): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    PlatformAdmin::query()->create([
        'user_id' => $user->getKey(),
        'is_active' => true,
        'role' => $role,
        'permissions' => $permissions,
    ]);

    return $user;
}

function v2Tenant(?User $owner = null): Tenant
{
    $owner ??= User::factory()->create(['email_verified_at' => now()]);
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    return app(CreateBusiness::class)->handle($owner, $type, [
        'name' => 'Super Admin V2 Clinic',
        'name_en' => 'Super Admin V2 Clinic',
        'name_ar' => 'عيادة المشرف',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);
}

test('platform role matrix limits platform routes and tenant permissions', function (): void {
    $viewer = v2Admin('viewer');
    $operations = v2Admin('operations_admin');
    $finance = v2Admin('finance_admin');
    $support = v2Admin('support_admin');
    $security = v2Admin('security_admin');
    $tenant = v2Tenant();

    expect(
        $this->actingAs($viewer)->get(route('admin.plans.index'))->status()
    )->toBe(403);

    $this->actingAs($operations)
        ->get(route('admin.businesses.create'))
        ->assertOk();

    $this->actingAs($operations)
        ->get(route('admin.plans.index'))
        ->assertForbidden();

    $this->actingAs($finance)
        ->get(route('admin.payments.index'))
        ->assertOk();

    $this->actingAs($finance)
        ->get(route('admin.support.index'))
        ->assertForbidden();

    $this->actingAs($support)
        ->get(route('admin.support.index'))
        ->assertOk();

    $this->actingAs($security)
        ->get(route('admin.security.index'))
        ->assertOk();

    app(\App\Domain\Tenant\Services\CurrentTenant::class)->set($tenant);

    expect(Gate::forUser($operations)->allows('bookings.create'))->toBeTrue()
        ->and(Gate::forUser($operations)->allows('subscription.manage'))->toBeFalse()
        ->and(Gate::forUser($finance)->allows('subscription.manage'))->toBeFalse()
        ->and(Gate::forUser($support)->allows('customers.update'))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('bookings.update'))->toBeFalse();
});

test('custom platform permissions can extend a read only role without changing the preset', function (): void {
    $viewer = v2Admin('viewer', ['plans.manage']);

    $this->actingAs($viewer)
        ->get(route('admin.plans.index'))
        ->assertOk();

    expect($viewer->fresh()->platformAdmin->role)->toBe('viewer')
        ->and($viewer->fresh()->platformAdmin->hasPlatformPermission('plans.manage'))->toBeTrue();
});

test('only super admins can change platform administrator roles', function (): void {
    $security = v2Admin('security_admin');
    $target = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($security)
        ->patch(route('admin.users.platform-admin-access', $target), [
            'role' => 'super_admin',
            'permissions' => [],
            'is_active' => 1,
        ])
        ->assertForbidden();

    $superAdmin = v2Admin('super_admin');

    $this->actingAs($superAdmin)
        ->patch(route('admin.users.platform-admin-access', $target), [
            'role' => 'support_admin',
            'permissions' => ['broadcast.manage'],
            'is_active' => 1,
        ])
        ->assertRedirect();

    $admin = $target->fresh()->platformAdmin;

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe('support_admin')
        ->and($admin->permissions)->toContain('broadcast.manage');
});

test('platform user security operations update profile verify email password sessions and two factor state', function (): void {
    Notification::fake();

    $admin = v2Admin();
    $target = User::factory()->create([
        'email' => 'old-user@example.com',
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_recovery_codes' => 'codes',
        'two_factor_confirmed_at' => now(),
        'password' => 'OldPassword123!',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Updated User',
            'email' => 'new-user@example.com',
        ])
        ->assertRedirect();

    expect($target->fresh()->name)->toBe('Updated User')
        ->and($target->fresh()->email)->toBe('new-user@example.com')
        ->and($target->fresh()->email_verified_at)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.users.verify-email', $target))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.users.reset-password', $target), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect();

    expect(Hash::check('NewPassword123!', $target->fresh()->password))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.disable-two-factor', $target))
        ->assertRedirect();

    $fresh = $target->fresh();

    expect($fresh->email_verified_at)->not->toBeNull()
        ->and($fresh->two_factor_secret)->toBeNull()
        ->and($fresh->two_factor_recovery_codes)->toBeNull()
        ->and($fresh->two_factor_confirmed_at)->toBeNull();
});

test('workspace archive is reversible and retained in the platform control plane', function (): void {
    $admin = v2Admin();
    $tenant = v2Tenant();

    $this->actingAs($admin)
        ->delete(route('admin.businesses.destroy', $tenant))
        ->assertRedirect(route('admin.businesses.index'));

    expect(Tenant::withTrashed()->find($tenant->getKey())?->deleted_at)->not->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.businesses.restore', $tenant->getKey()))
        ->assertRedirect(route('admin.businesses.show', $tenant));

    expect(Tenant::find($tenant->getKey())?->deleted_at)->toBeNull();

    expect(Activity::query()
        ->where('description', 'platform.workspace_archived')
        ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('description', 'platform.workspace_restored')
            ->exists())->toBeTrue();
});

test('support center provides a real two way conversation and notifies the requester', function (): void {
    Notification::fake();

    $owner = User::factory()->create(['email_verified_at' => now()]);
    $tenant = v2Tenant($owner);
    $admin = v2Admin();

    $this->actingAs($owner)
        ->post(route('support.store', $tenant), [
            'subject' => 'Booking issue',
            'message' => 'Customers cannot see the morning slots.',
            'priority' => 'high',
        ])
        ->assertRedirect();

    $ticket = SupportTicket::query()->where('tenant_id', $tenant->getKey())->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.support.reply', $ticket->getKey()), [
            'message' => 'We are investigating this issue now.',
        ])
        ->assertRedirect();

    expect(SupportTicketMessage::query()->where('support_ticket_id', $ticket->getKey())->count())->toBe(2);

    Notification::assertSentTo(
        $owner,
        SupportReplyNotification::class,
    );
});

test('platform broadcast reaches active members and records delivery history', function (): void {
    Notification::fake();

    $admin = v2Admin();
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $tenant = v2Tenant($owner);
    $member = User::factory()->create(['email_verified_at' => now()]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getKey(),
        'user_id' => $member->getKey(),
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.broadcasts.store'), [
            'tenant_id' => $tenant->getKey(),
            'title_en' => 'Maintenance',
            'title_ar' => 'صيانة',
            'message_en' => 'Scheduled maintenance tonight.',
            'message_ar' => 'توجد صيانة مجدولة الليلة.',
        ])
        ->assertRedirect();

    $broadcast = PlatformBroadcast::query()->latest('id')->firstOrFail();

    expect($broadcast->status)->toBe('sent')
        ->and($broadcast->recipients_count)->toBe(2);

    Notification::assertSentTo($owner, PlatformBroadcastNotification::class);
    Notification::assertSentTo($member, PlatformBroadcastNotification::class);
});

test('platform finance can verify and refund a payment through the provider contract', function (): void {
    $admin = v2Admin();
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $tenant = v2Tenant($owner);

    $payment = app(\App\Domain\Tenant\Services\CurrentTenant::class)->run($tenant, function () use ($tenant, $owner): Payment {
        return Payment::query()->create([
            'tenant_id' => $tenant->getKey(),
            'payable_type' => User::class,
            'payable_id' => $owner->getKey(),
            'reference' => 'PAY-SUPER-ADMIN-001',
            'provider' => 'test',
            'provider_reference' => 'PROVIDER-1',
            'amount_minor' => 5000,
            'currency' => 'EGP',
            'status' => PaymentStatus::Paid,
        ]);
    });

    $fakeGateway = new class implements PaymentGateway
    {
        public function createPayment(PaymentRequest $request): PaymentGatewayResult
        {
            return new PaymentGatewayResult(PaymentStatus::Processing, 'new-provider');
        }

        public function verifyPayment(string $providerReference): PaymentGatewayResult
        {
            return new PaymentGatewayResult(PaymentStatus::Paid, $providerReference);
        }

        public function refundPayment(string $providerReference, int $amountMinor): PaymentGatewayResult
        {
            return new PaymentGatewayResult(
                PaymentStatus::Refunded,
                $providerReference,
                metadata: ['refunded_amount_minor' => $amountMinor],
            );
        }
    };

    $this->app->instance(PaymentGateway::class, $fakeGateway);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $payment->getKey()))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.payments.refund', $payment->getKey()))
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

test('audit export returns a bounded CSV report', function (): void {
    $admin = v2Admin();

    app(AuditLogger::class)->log(
        'platform.super_admin_v2_test',
        $admin,
        ['tenant_id' => null],
    );

    $response = $this->actingAs($admin)
        ->get(route('admin.audit.export', ['search' => 'platform.super_admin_v2_test']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain('platform.super_admin_v2_test');
});


test('super admin can control the global module catalog while protecting core modules', function (): void {
    $admin = v2Admin();
    $payments = App\Domain\Module\Models\Module::query()->where('key', 'payments')->firstOrFail();
    $appointments = App\Domain\Module\Models\Module::query()->where('key', 'appointments')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.modules.update', $payments), [
            'name_en' => 'Payments',
            'name_ar' => 'المدفوعات',
            'description_en' => 'Customer payments',
            'description_ar' => 'مدفوعات العملاء',
            'is_active' => 0,
        ])
        ->assertRedirect();

    expect($payments->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->put(route('admin.modules.update', $appointments), [
            'name_en' => 'Appointments',
            'name_ar' => 'المواعيد',
            'description_en' => 'Appointments',
            'description_ar' => 'المواعيد',
            'is_active' => 0,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('is_active');

    expect($appointments->fresh()->is_active)->toBeTrue();
});

test('tenant cannot reply to a closed support ticket', function (): void {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $tenant = v2Tenant($owner);

    $ticket = SupportTicket::query()->create([
        'tenant_id' => $tenant->getKey(),
        'requester_user_id' => $owner->getKey(),
        'subject' => 'Closed ticket',
        'message' => 'This ticket is closed.',
        'status' => App\Domain\Support\Enums\SupportTicketStatus::Closed,
        'priority' => App\Domain\Support\Enums\SupportTicketPriority::Normal,
    ]);

    $this->actingAs($owner)
        ->post(route('support.reply', [$tenant, $ticket->getKey()]), [
            'message' => 'New reply',
        ])
        ->assertStatus(422);

    expect(SupportTicketMessage::query()->where('support_ticket_id', $ticket->getKey())->count())->toBe(0);
});
