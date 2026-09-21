<?php

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can belong to a tenant', function (): void {
    $user = User::factory()->create();
    $tenant = Tenant::query()->create([
        'slug' => 'demo-business',
        'status' => TenantStatus::Active,
    ]);

    $membership = TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    expect($membership->tenant->is($tenant))->toBeTrue()
        ->and($membership->user->is($user))->toBeTrue()
        ->and($tenant->users->contains($user))->toBeTrue();
});

test('tenant owned models are isolated by current tenant context', function (): void {
    $tenantA = Tenant::query()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::query()->create(['slug' => 'tenant-b']);

    expect(app(CurrentTenant::class)->id())->toBeNull();

    app(CurrentTenant::class)->set($tenantA);

    $first = new class extends \Illuminate\Database\Eloquent\Model {
        use \App\Domain\Tenant\Concerns\BelongsToTenant;

        protected $table = 'tenant_memberships';

        protected $guarded = [];
    };

    expect($first->query()->count())->toBe(0);
});
