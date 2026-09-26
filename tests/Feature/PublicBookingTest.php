<?php

use App\Domain\Booking\Models\Booking;
use App\Domain\Scheduling\Actions\SetBusinessWorkingHours;
use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Service\Actions\CreateService;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Business\Models\BusinessProfile;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
});

function publicTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'description' => ['en' => 'Public booking business'],
        'timezone' => 'Africa/Cairo',
    ]);

    app(SetBusinessWorkingHours::class)->handle([
        ['day_of_week' => DayOfWeek::Monday->value, 'opens_at' => '09:00', 'closes_at' => '17:00'],
    ]);

    return $tenant;
}

test('public business page is available without authentication', function (): void {
    $tenant = publicTenant('public-clinic');

    app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.show', $tenant->slug))
        ->assertOk()
        ->assertSee('Public booking business')
        ->assertSee('Consultation');
});

test('public availability is tenant scoped and returns slots', function (): void {
    $tenant = publicTenant('availability-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
        'buffer_minutes' => 10,
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->get(route('public.booking.availability', $tenant->slug).'?'.http_build_query([
        'service_id' => $service->id,
        'date' => '2026-09-28',
    ]));

    $response->assertOk()
        ->assertJsonStructure(['data']);

    expect($response->json('data'))->not->toBeEmpty();
});

test('public customer can book without an account', function (): void {
    $tenant = publicTenant('book-clinic');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $response = $this->post(route('public.booking.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ]);

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    app(CurrentTenant::class)->set($tenant);
    $booking->load('customer');

    $response->assertRedirect(URL::signedRoute('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]));

    expect($booking->customer->name)->toBe('Ahmed')
        ->and($booking->service_id)->toBe($service->id);
});

test('public booking confirmation requires a signed url', function (): void {
    $tenant = publicTenant('confirmation-secure');

    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Consultation'],
        'price_minor' => 20000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.store', $tenant->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Ahmed',
        'phone' => '+20 100 123 4567',
        'email' => 'ahmed@example.com',
    ])->assertRedirect();

    $booking = Booking::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

    $this->get(route('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]))->assertForbidden();

    $this->get(URL::signedRoute('public.booking.confirmation', [
        'tenant' => $tenant->slug,
        'booking' => $booking->booking_reference,
    ]))
        ->assertOk()
        ->assertSee($booking->booking_reference);
});

test('public booking cannot use a service from another tenant', function (): void {
    $tenantA = publicTenant('tenant-a');
    $service = app(CreateService::class)->handle([
        'name' => ['en' => 'Private Service'],
        'price_minor' => 10000,
        'duration_minutes' => 30,
    ]);

    app(CurrentTenant::class)->clear();
    $tenantB = publicTenant('tenant-b');
    app(CurrentTenant::class)->clear();

    $this->post(route('public.booking.store', $tenantB->slug), [
        'service_id' => $service->id,
        'date' => '2026-09-28',
        'time' => '10:00',
        'name' => 'Intruder',
    ])->assertSessionHasErrors('service_id');

    expect(Booking::withoutGlobalScopes()->count())->toBe(0);
});


test('public booking page renders localized Arabic content', function (): void {
    $tenant = publicTenant('arabic-clinic');

    app(CurrentTenant::class)->set($tenant);
    $tenant->profile()->update([
        'name' => ['en' => 'Arabic Clinic', 'ar' => 'عيادة عربية'],
        'description' => ['en' => 'English description', 'ar' => 'وصف عربي'],
    ]);

    app(CurrentTenant::class)->clear();

    $this->get(route('public.booking.show', $tenant->slug).'?locale=ar')
        ->assertOk()
        ->assertSee('<html lang="ar" dir="rtl">', false)
        ->assertSee('عيادة عربية')
        ->assertSee('وصف عربي')
        ->assertSee('الخدمة')
        ->assertSee('اختر خدمة');

    expect(app()->getLocale())->toBe('ar');
});
