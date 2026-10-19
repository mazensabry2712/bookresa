<?php

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\CreateSubscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Actions\AddStaffMember;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Support\PlatformSettings;
use Carbon\CarbonImmutable;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        ModuleSeeder::class,
        BusinessTypeSeeder::class,
    ]);
});

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
});

function workspaceOwner(string $name = 'Workspace Owner'): array
{
    $user = User::factory()->create([
        'name' => $name,
    ]);

    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $tenant = app(CreateBusiness::class)->handle($user, $type, [
        'name' => 'Owner Clinic',
        'name_en' => 'Owner Clinic',
        'name_ar' => 'عيادة المالك',
        'timezone' => 'Africa/Cairo',
        'locale' => 'en',
    ]);

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.step', 'ready');
    data_set($settings, 'onboarding.completed', true);
    $tenant->forceFill(['settings' => $settings])->save();

    app(CurrentTenant::class)->set($tenant);

    $plan = Plan::query()->create([
        'name' => ['en' => 'Test Core Plan'],
        'description' => ['en' => 'Test core plan'],
        'price_minor' => 0,
        'currency' => 'EGP',
        'billing_period' => PlanBillingPeriod::Monthly,
        'included_customer_limit' => 100,
        'additional_customer_price_minor' => 0,
        'trial_days' => 0,
        'is_active' => true,
    ]);

    $subscription = app(CreateSubscription::class)->handle(
        $plan,
        CarbonImmutable::now(),
    );

    $subscription->forceFill([
        'payment_status' => PaymentStatus::Paid,
        'end_at' => CarbonImmutable::now()->addMonth(),
        'pricing_snapshot' => ['modules' => []],
    ])->save();

    return [$user, $tenant];
}

test('owner can view and update business profile', function (): void {
    [$user, $tenant] = workspaceOwner();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('business.profile.edit', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Business profile')
        ->assertSee('Owner Clinic')
        ->assertSee('Minimum notice')
        ->assertSee('Maximum advance');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->put(route('business.profile.update', ['tenant' => $tenant->slug]), [
            'name_en' => 'Updated Clinic',
            'name_ar' => 'العيادة المحدثة',
            'description_en' => 'Updated description',
            'description_ar' => 'وصف محدث',
            'phone' => '01012345678',
            'email' => 'clinic@example.com',
            'location' => 'Cairo',
            'address' => '123 Main Street',
            'website' => 'https://example.com',
            'facebook' => 'https://facebook.com/elnour',
            'instagram' => 'https://instagram.com/elnour',
            'timezone' => 'Africa/Cairo',
            'locale' => 'ar',
            'payment_mode' => 'pay_later',
            'customer_email_required' => true,
            'customer_limit_policy' => 'allow_overage',
            'minimum_notice_minutes' => 60,
            'maximum_advance_days' => 90,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    app(CurrentTenant::class)->set($tenant);

    expect($tenant->fresh('profile')->profile->name['en'])->toBe('Updated Clinic')
        ->and($tenant->fresh('profile')->profile->locale)->toBe('ar')
        ->and($tenant->fresh('profile')->profile->phone)->toBe('01012345678')
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'minimum_notice_minutes'))->toBe(60)
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'maximum_advance_days'))->toBe(90)
        ->and(data_get($tenant->fresh('profile')->profile->booking_settings, 'customer_email_required'))->toBeTrue()
        ->and(data_get($tenant->fresh('profile')->profile->social_links, 'website'))->toBe('https://example.com')
        ->and(data_get($tenant->fresh('profile')->profile->social_links, 'facebook'))->toBe('https://facebook.com/elnour')
        ->and(data_get($tenant->fresh('profile')->profile->social_links, 'instagram'))->toBe('https://instagram.com/elnour');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('business.profile.edit', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('<option value="ar" selected>', false)
        ->assertDontSee('<option value="ar" selected">', false);

    $this->get(route('public.booking.canonical.show', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('https://example.com', false)
        ->assertSee('https://facebook.com/elnour', false)
        ->assertSee('https://instagram.com/elnour', false);
});

test('workspace members without business update permission see a read-only profile', function (): void {
    app()->setLocale('en');

    [$owner, $tenant] = workspaceOwner();

    $viewer = User::factory()->create([
        'name' => 'Read Only Staff',
        'email_verified_at' => now(),
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getKey(),
        'user_id' => $viewer->getKey(),
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    setPermissionsTeamId($tenant->getKey());
    $staffRole = app(TenantRoleProvisioner::class)->provisionRole($tenant, 'staff');
    $viewer->assignRole($staffRole);
    setPermissionsTeamId(null);

    $response = $this->actingAs($viewer)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->get(route('business.profile.edit', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('data-business-profile-editable="false"', false)
        ->assertSee(__('app.business_ui.read_only_help'))
        ->assertDontSee(__('app.business_ui.save_changes'));

    expect(preg_match('/name="name_en"[^>]*\sdisabled(?:\s|>)/', $response->getContent()))->toBe(1);

    $this->actingAs($viewer)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->put(route('business.profile.update', ['tenant' => $tenant->slug]), [])
        ->assertForbidden();

    app(CurrentTenant::class)->set($tenant);

    expect($tenant->fresh('profile')->profile->name['en'])->toBe('Owner Clinic');
});

test('workspace profile does not configure payment accounts and follows centralized platform policy', function (): void {
    [$user, $tenant] = workspaceOwner();
    $existingPaymentAccountCount = TenantPaymentAccount::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->count();

    app(PlatformSettings::class)->put([
        'centralized_booking_payments_enabled' => '1',
        'commission_free_customer_limit' => '1000',
        'booking_commission_rate_basis_points' => '500',
    ]);

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id, 'locale' => 'en'])
        ->get(route('business.profile.edit', ['tenant' => $tenant->slug, 'locale' => 'en']))
        ->assertOk()
        ->assertSee('Business profile')
        ->assertSee('A valid customer email is required for secure online payment and payment receipts.')
        ->assertDontSee('payment_merchant_id')
        ->assertDontSee('name="payment_mode"')
        ->assertDontSee('Booking payment account')
        ->assertDontSee('app.merchant_id');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->put(route('business.profile.update', ['tenant' => $tenant->slug]), [
            'name_en' => 'Central Clinic',
            'name_ar' => 'عيادة مركزية',
            'description_en' => 'Central payments enabled',
            'description_ar' => 'الدفع المركزي مفعل',
            'timezone' => 'Africa/Cairo',
            'locale' => 'en',
            'customer_limit_policy' => 'allow_overage',
            'minimum_notice_minutes' => 0,
            'maximum_advance_days' => 90,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    app(CurrentTenant::class)->set($tenant);
    $settings = $tenant->fresh('profile')->profile->booking_settings;

    expect(data_get($settings, 'payment_required'))->toBeTrue()
        ->and(data_get($settings, 'payment_mode'))->toBe('full')
        ->and(data_get($settings, 'customer_email_required'))->toBeTrue()
        ->and(TenantPaymentAccount::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())
        ->toBe($existingPaymentAccountCount);
});

test('owner can create and update a service through workspace ui', function (): void {
    [$user, $tenant] = workspaceOwner();

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store', ['tenant' => $tenant->slug]), [
            'name_en' => 'Haircut',
            'name_ar' => 'حلاقة',
            'description_en' => 'Classic haircut',
            'description_ar' => 'حلاقة كلاسيكية',
            'price' => '150.00',
            'currency' => 'egp',
            'duration_minutes' => 30,
            'buffer_minutes' => 10,
            'is_active' => 1,
        ])
        ->assertRedirect(route('services.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    $service = Service::query()->firstOrFail();

    expect($service->tenant_id)->toBe($tenant->id)
        ->and($service->price_minor)->toBe(15000)
        ->and($service->currency)->toBe('EGP');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->put(route('services.update', ['tenant' => $tenant->slug, 'service' => $service]), [
            'name_en' => 'Premium Haircut',
            'name_ar' => 'حلاقة فاخرة',
            'description_en' => 'Premium',
            'description_ar' => '',
            'price' => '175.50',
            'currency' => 'EGP',
            'duration_minutes' => 45,
            'buffer_minutes' => 5,
            'is_active' => 1,
        ])
        ->assertRedirect(route('services.index', ['tenant' => $tenant->slug]));

    expect($service->fresh()->price_minor)->toBe(17550)
        ->and($service->fresh()->duration_minutes)->toBe(45);
});

test('creating the first service during onboarding opens scheduling next', function (): void {
    [$user, $tenant] = workspaceOwner();

    $settings = $tenant->settings ?? [];
    data_set($settings, 'onboarding.completed', false);
    data_set($settings, 'onboarding.step', 'services');
    $tenant->forceFill(['settings' => $settings])->save();

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store', ['tenant' => $tenant->slug]), [
            'name_en' => 'Onboarding consultation',
            'name_ar' => 'استشارة الإعداد',
            'description_en' => 'First service',
            'description_ar' => 'أول خدمة',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertRedirect(route('scheduling.index', ['tenant' => $tenant->slug]));

    expect(data_get($tenant->fresh()->settings, 'onboarding.step'))->toBe('hours')
        ->and((bool) data_get($tenant->fresh()->settings, 'onboarding.completed'))->toBeFalse();
});

test('service price validation rejects excessive precision and unsafe amounts', function (): void {
    [$user, $tenant] = workspaceOwner();

    $basePayload = [
        'name_en' => 'Price validation service',
        'name_ar' => 'خدمة اختبار السعر',
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
        'is_active' => 1,
    ];

    foreach (['12.345', '10000000000.00'] as $invalidPrice) {
        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->from(route('services.index', ['tenant' => $tenant->slug]))
            ->post(route('services.store', ['tenant' => $tenant->slug]), [
                ...$basePayload,
                'price' => $invalidPrice,
            ])
            ->assertRedirect(route('services.index', ['tenant' => $tenant->slug]))
            ->assertSessionHasErrors('price');
    }

    app(CurrentTenant::class)->set($tenant);

    expect(Service::query()->count())->toBe(0);
});

test('workspace service management stays tenant isolated', function (): void {
    [$user, $tenantA] = workspaceOwner('Tenant A Owner');
    [$otherUser, $tenantB] = workspaceOwner('Tenant B Owner');

    $this->actingAs($user)->withSession(['tenant_id' => $tenantA->id])
        ->post(route('services.store', ['tenant' => $tenantA->slug]), [
            'name_en' => 'Tenant A Service',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 20,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ]);

    app(CurrentTenant::class)->set($tenantA);

    $serviceA = Service::query()->firstOrFail();

    $this->actingAs($otherUser)->withSession(['tenant_id' => $tenantB->id])
        ->get(route('services.index', ['tenant' => $tenantB->slug, 'edit' => $serviceA->id]))
        ->assertNotFound();

    app(CurrentTenant::class)->set($tenantB);

    expect(Service::query()->count())->toBe(0);
    expect($otherUser->tenantMemberships()->where('tenant_id', $tenantB->id)->exists())->toBeTrue();
});

test('receptionist cannot manage services', function (): void {
    [$owner, $tenant] = workspaceOwner();
    $staffUser = User::factory()->create();

    app(CurrentTenant::class)->set($tenant);
    app(AddStaffMember::class)->handle($staffUser, 'receptionist');

    $this->actingAs($staffUser)->withSession(['tenant_id' => $tenant->id])
        ->post(route('services.store', ['tenant' => $tenant->slug]), [
            'name_en' => 'Blocked',
            'price' => '100.00',
            'currency' => 'EGP',
            'duration_minutes' => 20,
            'buffer_minutes' => 0,
            'is_active' => 1,
        ])
        ->assertForbidden();

    expect(Service::query()->count())->toBe(0);

    unset($owner);
});

test('service delete is blocked when booking history exists', function (): void {
    [$user, $tenant] = workspaceOwner();
    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Booked Service', 'ar' => 'خدمة محجوزة'],
        'price_minor' => 10000,
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    // The actual booking domain test suite owns booking creation; this test
    // verifies the management UI can never delete a referenced service once
    // a booking record exists.
    DB::table('bookings')->insert([
        'tenant_id' => $tenant->id,
        'customer_id' => Customer::factory()->create()->id,
        'service_id' => $service->id,
        'staff_id' => null,
        'starts_at' => '2026-10-05 10:00:00',
        'ends_at' => '2026-10-05 10:30:00',
        'block_ends_at' => '2026-10-05 10:30:00',
        'status' => 'confirmed',
        'payment_status' => 'unpaid',
        'booking_reference' => 'BR-TEST-DELETE-1',
        'notes' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->delete(route('services.destroy', ['tenant' => $tenant->slug, 'service' => $service]))
        ->assertRedirect()
        ->assertSessionHasErrors('service');

    expect($service->fresh())->not->toBeNull()
        ->and($service->fresh()->is_active)->toBeTrue();
});

test('service management paginates large service lists', function (): void {
    [$user, $tenant] = workspaceOwner();

    app(CurrentTenant::class)->set($tenant);

    for ($i = 1; $i <= 21; $i++) {
        Service::query()->create([
            'tenant_id' => $tenant->id,
            'name' => ['en' => 'Service '.$i, 'ar' => 'خدمة '.$i],
            'price_minor' => 10000,
            'currency' => 'EGP',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);
    }

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Service 21')
        ->assertDontSee('<p class="font-semibold">Service 1</p>');

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('services.index', ['tenant' => $tenant->slug, 'page' => 2]))
        ->assertOk()
        ->assertSee('Service 1');
});


test('staff creation assigns tenant services and duplicate submission cannot silently reactivate existing staff', function (): void {
    [$owner, $tenant] = workspaceOwner('Staff Management Owner');

    app(CurrentTenant::class)->set($tenant);

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Haircut', 'ar' => 'حلاقة'],
        'price_minor' => 15000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 5,
    ]);

    $staffUser = User::factory()->create([
        'name' => 'Test Staff Member',
        'email' => 'staff.member@example.com',
    ]);

    $payload = [
        'email' => $staffUser->email,
        'display_name' => 'Test Staff Member',
        'phone' => '01000000000',
        'job_title' => 'Barber',
        'role' => 'staff',
        'services' => [$service->id],
    ];

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('staff.store', ['tenant' => $tenant->slug]), $payload)
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]));

    app(CurrentTenant::class)->set($tenant);

    $staff = StaffProfile::query()->where('user_id', $staffUser->getKey())->firstOrFail();

    expect($staff->status->value)->toBe('active')
        ->and($staff->job_title)->toBe('Barber')
        ->and($staff->services->pluck('id')->map(fn ($id): int => (int) $id)->all())->toBe([$service->id]);

    $staff->update(['status' => 'inactive']);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('staff.index', ['tenant' => $tenant->slug]))
        ->post(route('staff.store', ['tenant' => $tenant->slug]), [
            ...$payload,
            'display_name' => 'Unexpected overwrite',
            'job_title' => 'Unexpected role change',
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors('email');

    app(CurrentTenant::class)->set($tenant);

    expect($staff->fresh()->status->value)->toBe('inactive')
        ->and($staff->fresh()->display_name)->toBe('Test Staff Member')
        ->and($staff->fresh()->job_title)->toBe('Barber');
});

test('staff creation rejects service assignments from another workspace before creating membership', function (): void {
    [$owner, $tenant] = workspaceOwner('Staff Tenant A');
    [$otherOwner, $otherTenant] = workspaceOwner('Staff Tenant B');

    app(CurrentTenant::class)->set($otherTenant);

    $foreignService = app(CreateService::class)->handle([
        'name' => ['en' => 'Foreign service', 'ar' => 'خدمة خارجية'],
        'price_minor' => 10000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $staffUser = User::factory()->create([
        'name' => 'Foreign Service Staff',
        'email' => 'foreign.service.staff@example.com',
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('staff.index', ['tenant' => $tenant->slug]))
        ->post(route('staff.store', ['tenant' => $tenant->slug]), [
            'email' => $staffUser->email,
            'role' => 'staff',
            'services' => [$foreignService->id],
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors('services.0');

    app(CurrentTenant::class)->set($tenant);

    expect(StaffProfile::query()->where('user_id', $staffUser->getKey())->exists())->toBeFalse();

    unset($otherOwner);
});


test('staff updates reject foreign-workspace services without changing status or current assignments', function (): void {
    [$owner, $tenant] = workspaceOwner('Staff Update Tenant A');
    [$otherOwner, $otherTenant] = workspaceOwner('Staff Update Tenant B');

    app(CurrentTenant::class)->set($tenant);

    $currentService = app(CreateService::class)->handle([
        'name' => ['en' => 'Current service', 'ar' => 'الخدمة الحالية'],
        'price_minor' => 12000,
        'currency' => 'EGP',
        'duration_minutes' => 30,
        'buffer_minutes' => 0,
    ]);

    $staffUser = User::factory()->create([
        'name' => 'Workspace Staff',
        'email' => 'workspace.staff.update@example.com',
    ]);

    $staff = app(AddStaffMember::class)->handle($staffUser, 'staff', [
        'display_name' => 'Workspace Staff',
        'job_title' => 'Specialist',
    ]);

    $staff->services()->syncWithPivotValues(
        [$currentService->id],
        ['tenant_id' => $tenant->id],
    );

    app(CurrentTenant::class)->set($otherTenant);

    $foreignService = app(CreateService::class)->handle([
        'name' => ['en' => 'Foreign service', 'ar' => 'خدمة خارجية'],
        'price_minor' => 9000,
        'currency' => 'EGP',
        'duration_minutes' => 20,
        'buffer_minutes' => 0,
    ]);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->id])
        ->from(route('staff.index', ['tenant' => $tenant->slug]))
        ->put(route('staff.update', ['tenant' => $tenant->slug, 'staff' => $staff]), [
            'display_name' => 'Changed Name',
            'phone' => '01000000000',
            'job_title' => 'Changed title',
            'role' => 'receptionist',
            'status' => 'inactive',
            'services' => [$foreignService->id],
        ])
        ->assertRedirect(route('staff.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors('services.0');

    app(CurrentTenant::class)->set($tenant);

    expect($staff->fresh()->status->value)->toBe('active')
        ->and($staff->fresh()->display_name)->toBe('Workspace Staff')
        ->and($staff->fresh()->job_title)->toBe('Specialist')
        ->and($staff->fresh()->services->pluck('id')->map(fn ($id): int => (int) $id)->all())->toBe([$currentService->id]);

    unset($otherOwner);
});
