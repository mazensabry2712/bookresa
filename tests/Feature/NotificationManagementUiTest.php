<?php

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function notificationUiTenant(string $slug): Tenant
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

function notificationUiUser(Tenant $tenant, string $email): User
{
    $user = User::factory()->create(['email' => $email]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    setPermissionsTeamId($tenant->id);

    $permission = Permission::firstOrCreate([
        'name' => 'notifications.view',
        'guard_name' => 'web',
    ]);

    $role = Role::firstOrCreate([
        'name' => 'notifications-owner',
        'guard_name' => 'web',
        'tenant_id' => $tenant->id,
    ]);

    $role->syncPermissions([$permission]);
    $user->assignRole($role);

    return $user;
}

function notificationUiRecord(User $user, Tenant $tenant, string $title, bool $read = false): string
{
    $id = (string) Str::uuid();

    DB::table('notifications')->insert([
        'id' => $id,
        'type' => 'test',
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'data' => json_encode([
            'type' => 'booking_created',
            'tenant_id' => $tenant->getKey(),
            'title' => ['en' => $title, 'ar' => $title],
            'message' => ['en' => $title, 'ar' => $title],
        ], JSON_THROW_ON_ERROR),
        'read_at' => $read ? now() : null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $id;
}

test('notification list is scoped before pagination', function (): void {
    $tenant = notificationUiTenant('notifications-page');
    $otherTenant = notificationUiTenant('notifications-other');
    $user = notificationUiUser($tenant, 'notifications@example.com');

    TenantMembership::query()->create([
        'tenant_id' => $otherTenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    foreach (range(1, 100) as $index) {
        notificationUiRecord($user, $otherTenant, 'Foreign notification '.$index);
    }

    notificationUiRecord($user, $tenant, 'Current workspace notification');

    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->get(route('notifications.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Current workspace notification')
        ->assertDontSee('Foreign notification 100')
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 1
                && $notifications->perPage() === 25;
        });
});

test('mark all read only updates the current workspace notifications', function (): void {
    $tenant = notificationUiTenant('notifications-read-all');
    $otherTenant = notificationUiTenant('notifications-read-other');
    $user = notificationUiUser($tenant, 'notifications-read@example.com');

    TenantMembership::query()->create([
        'tenant_id' => $otherTenant->id,
        'user_id' => $user->id,
        'status' => MembershipStatus::Active,
        'is_primary' => false,
    ]);

    $current = notificationUiRecord($user, $tenant, 'Current unread');
    $foreign = notificationUiRecord($user, $otherTenant, 'Foreign unread');

    app(CurrentTenant::class)->set($tenant);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->id])
        ->post(route('notifications.read-all', ['tenant' => $tenant->slug]))
        ->assertSessionHas('status');

    expect(DB::table('notifications')->where('id', $current)->value('read_at'))->not->toBeNull()
        ->and(DB::table('notifications')->where('id', $foreign)->value('read_at'))->toBeNull();
});
