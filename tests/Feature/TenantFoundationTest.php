<?php

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can belong to an active tenant', function (): void {
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

test('current tenant context is isolated and can be temporarily switched', function (): void {
    $tenantA = Tenant::query()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::query()->create(['slug' => 'tenant-b']);

    $context = app(CurrentTenant::class);

    expect($context->id())->toBeNull();

    $context->set($tenantA);

    expect($context->get()->is($tenantA))->toBeTrue()
        ->and($context->id())->toBe($tenantA->id);

    $result = $context->run($tenantB, fn (): int => $context->idOrFail());

    expect($result)->toBe($tenantB->id)
        ->and($context->id())->toBe($tenantA->id);

    $context->clear();

    expect($context->id())->toBeNull();
});

test('tenant-owned models are automatically scoped to the current tenant', function (): void {
    $tenantA = Tenant::query()->create(['slug' => 'tenant-a']);
    $tenantB = Tenant::query()->create(['slug' => 'tenant-b']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $userA->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenantB->id,
        'user_id' => $userB->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    $context = app(CurrentTenant::class);
    $context->set($tenantA);

    $tenantAwareMembership = new class extends Model
    {
        use \App\Domain\Tenant\Concerns\BelongsToTenant;

        protected $table = 'tenant_memberships';

        protected $guarded = [];
    };

    expect($tenantAwareMembership->newQuery()->count())->toBe(1)
        ->and($tenantAwareMembership->newQuery()->first()->user_id)->toBe($userA->id);
});

test('tenant-owned models refuse writes without a current tenant', function (): void {
    $tenantAwareMembership = new class extends Model
    {
        use \App\Domain\Tenant\Concerns\BelongsToTenant;

        protected $table = 'tenant_memberships';

        protected $guarded = [];
    };

    expect(fn () => $tenantAwareMembership->newQuery()->create([
        'user_id' => User::factory()->create()->id,
        'status' => MembershipStatus::Active->value,
    ]))->toThrow(LogicException::class);
});
