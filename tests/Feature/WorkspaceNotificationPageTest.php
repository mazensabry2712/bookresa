<?php

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app(CurrentTenant::class)->clear();
    setPermissionsTeamId(null);
    app()->setLocale('en');
});

/** @return Tenant */
function workspaceNotificationsTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->getKey(),
        'name' => ['en' => $slug, 'ar' => $slug],
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

/**
 * @param list<string> $permissions
 */
function workspaceNotificationsUser(
    Tenant $tenant,
    string $email,
    array $permissions = ['notifications.view', 'support.view'],
): User {
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getKey(),
        'user_id' => $user->getKey(),
        'status' => MembershipStatus::Active,
        'is_primary' => true,
    ]);

    setPermissionsTeamId($tenant->getKey());

    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $role = Role::firstOrCreate([
        'name' => 'notification-page-'.str_replace(['@', '.'], '-', $email),
        'guard_name' => 'web',
        'tenant_id' => $tenant->getKey(),
    ]);

    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

/**
 * @param array<string, mixed> $data
 */
function workspaceNotificationRecord(
    User $user,
    Tenant $tenant,
    array $data,
    ?\Illuminate\Support\Carbon $readAt = null,
): \Illuminate\Database\Eloquent\Model {
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test.notification',
        'data' => array_merge(['tenant_id' => $tenant->getKey()], $data),
        'read_at' => $readAt,
    ]);
}

test('workspace notifications are tenant scoped and unread count is accurate', function (): void {
    app()->setLocale('en');

    $tenant = workspaceNotificationsTenant('notification-page-current');
    $user = workspaceNotificationsUser($tenant, 'notification-page-current@example.test');

    workspaceNotificationRecord($user, $tenant, [
        'type' => 'platform_broadcast',
        'title' => 'A visible announcement',
        'message' => 'A message for this workspace.',
    ]);

    workspaceNotificationRecord($user, $tenant, [
        'type' => 'support_reply',
        'title' => ['en' => 'A previous support reply', 'ar' => 'رد سابق'],
        'message' => ['en' => 'This notification was read.', 'ar' => 'تمت قراءة الإشعار.'],
    ], now());

    $otherTenant = workspaceNotificationsTenant('notification-page-other');

    workspaceNotificationRecord($user, $otherTenant, [
        'type' => 'platform_broadcast',
        'title' => ['en' => 'Private other workspace announcement', 'ar' => 'إعلان مساحة أخرى'],
        'message' => ['en' => 'Must stay private.', 'ar' => 'يجب أن يظل خاصًا.'],
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->get(route('notifications.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('A visible announcement')
        ->assertSee('A message for this workspace.')
        ->assertDontSee('Private other workspace announcement')
        ->assertViewHas('unreadCount', 1)
        ->assertViewHas('notifications', function ($notifications): bool {
            return $notifications->total() === 2;
        });
});

test('opening a support reply marks it read and opens the workspace ticket', function (): void {
    $tenant = workspaceNotificationsTenant('notification-page-support');
    $user = workspaceNotificationsUser($tenant, 'notification-page-support@example.test');

    app(CurrentTenant::class)->set($tenant);

    $ticket = SupportTicket::query()->create([
        'tenant_id' => $tenant->getKey(),
        'requester_user_id' => $user->getKey(),
        'subject' => 'Notification navigation test ticket',
        'message' => 'Test ticket used to verify support notification navigation.',
        'status' => SupportTicketStatus::Open,
        'priority' => SupportTicketPriority::Normal,
        'last_replied_at' => now(),
    ]);

    $notification = workspaceNotificationRecord($user, $tenant, [
        'type' => 'support_reply',
        'ticket_id' => $ticket->getKey(),
        'title' => ['en' => 'New support reply', 'ar' => 'رد دعم جديد'],
        'message' => ['en' => 'The support team replied.', 'ar' => 'رد فريق الدعم.'],
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->post(route('notifications.read', [
            'tenant' => $tenant->slug,
            'notification' => $notification->getKey(),
        ]))
        ->assertRedirect(route('support.show', [
            'tenant' => $tenant->slug,
            'ticket' => $ticket->getKey(),
        ]));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all read only updates notifications for the active workspace', function (): void {
    $tenant = workspaceNotificationsTenant('notification-page-read-all');
    $user = workspaceNotificationsUser(
        $tenant,
        'notification-page-read-all@example.test',
        ['notifications.view'],
    );

    $currentWorkspaceNotification = workspaceNotificationRecord($user, $tenant, [
        'type' => 'platform_broadcast',
        'title' => ['en' => 'Current workspace', 'ar' => 'مساحة العمل الحالية'],
        'message' => ['en' => 'Current message', 'ar' => 'رسالة حالية'],
    ]);

    $otherTenant = workspaceNotificationsTenant('notification-page-read-all-other');
    $otherWorkspaceNotification = workspaceNotificationRecord($user, $otherTenant, [
        'type' => 'platform_broadcast',
        'title' => ['en' => 'Other workspace', 'ar' => 'مساحة أخرى'],
        'message' => ['en' => 'Other message', 'ar' => 'رسالة أخرى'],
    ]);

    $this->actingAs($user)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->from(route('notifications.index', ['tenant' => $tenant->slug]))
        ->post(route('notifications.read-all', ['tenant' => $tenant->slug]))
        ->assertRedirect(route('notifications.index', ['tenant' => $tenant->slug]));

    expect($currentWorkspaceNotification->fresh()->read_at)->not->toBeNull()
        ->and($otherWorkspaceNotification->fresh()->read_at)->toBeNull();
});
