<?php

use App\Domain\Business\Models\BusinessType;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\BusinessTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(BusinessTypeSeeder::class);
});

test('public booking page renders reusable seo metadata and structured data', function (): void {
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();
    $tenant = Tenant::factory()->create([
        'business_type_id' => $type->id,
        'slug' => 'seo-clinic',
        'status' => TenantStatus::Active,
    ]);

    $tenant->profile()->create([
        'name' => ['en' => 'SEO Clinic', 'ar' => 'عيادة SEO'],
        'description' => ['en' => 'A searchable clinic landing page.', 'ar' => 'صفحة عيادة قابلة للبحث.'],
        'phone' => '01000000000',
    ]);

    $this->get(route('public.booking.show', $tenant))
        ->assertOk()
        ->assertSee('<title>SEO Clinic — BookResa</title>', false)
        ->assertSee('name="description"', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('property="og:type"', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('https://schema.org', false)
        ->assertSee('SEO Clinic', false);
});

test('private dashboards send noindex metadata', function (): void {
    $user = User::factory()->create();
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();
    $tenant = Tenant::factory()->create([
        'business_type_id' => $type->id,
    ]);

    $tenant->memberships()->create([
        'user_id' => $user->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])
        ->get(route('onboarding.workspace'))
        ->assertOk()
        ->assertSee('name="robots" content="noindex,nofollow,noarchive"', false);
});

test('sitemap includes active public booking pages only', function (): void {
    $type = BusinessType::query()->where('slug', 'clinic')->firstOrFail();

    $active = Tenant::factory()->create([
        'business_type_id' => $type->id,
        'slug' => 'active-clinic',
        'status' => TenantStatus::Active,
    ]);

    $inactive = Tenant::factory()->create([
        'business_type_id' => $type->id,
        'slug' => 'inactive-clinic',
        'status' => TenantStatus::Suspended,
    ]);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('public.booking.show', $active), false)
        ->assertDontSee(route('public.booking.show', $inactive), false);
});

test('robots disallows private areas and points to sitemap', function (): void {
    $this->get(route('robots.txt'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /dashboard/', false)
        ->assertSee('Disallow: /admin/', false)
        ->assertSee('Disallow: /onboarding/', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});
