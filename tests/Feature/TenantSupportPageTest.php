<?php

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Notifications\TenantSupportActivityNotification;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function (): void {
    setPermissionsTeamId(null);
    app(CurrentTenant::class)->clear();
});

function tenantSupportPageTenant(string $slug): Tenant
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
 * @param  list<string>  $permissions
 */
function tenantSupportPageUser(Tenant $tenant, string $email, array $permissions = ['support.view', 'support.create', 'support.reply']): User
{
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
        'name' => 'support-page-'.str_replace(['@', '.'], '-', $email),
        'guard_name' => 'web',
        'tenant_id' => $tenant->getKey(),
    ]);
    $role->syncPermissions($permissions);
    $user->assignRole($role);

    return $user;
}

function tenantSupportPageTicket(
    Tenant $tenant,
    User $requester,
    string $subject,
    SupportTicketStatus $status = SupportTicketStatus::Open,
    SupportTicketPriority $priority = SupportTicketPriority::Normal,
    string $message = 'A detailed support message describing the issue.',
): SupportTicket {
    app(CurrentTenant::class)->set($tenant);

    $ticket = SupportTicket::query()->create([
        'tenant_id' => $tenant->getKey(),
        'requester_user_id' => $requester->getKey(),
        'subject' => $subject,
        'message' => $message,
        'status' => $status,
        'priority' => $priority,
        'last_replied_at' => now(),
        'resolved_at' => in_array($status, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true)
            ? now()
            : null,
    ]);

    SupportTicketMessage::query()->create([
        'support_ticket_id' => $ticket->getKey(),
        'author_user_id' => $requester->getKey(),
        'author_kind' => 'tenant',
        'message' => $message,
    ]);

    return $ticket;
}

test('workspace support page lists only current workspace tickets with accurate unfiltered status counts', function (): void {
    app()->setLocale('en');

    $tenantA = tenantSupportPageTenant('support-page-list-a');
    $ownerA = tenantSupportPageUser($tenantA, 'support-page-list-a@example.test');
    $openTicket = tenantSupportPageTicket(
        $tenantA,
        $ownerA,
        'Booking availability problem',
        SupportTicketStatus::Open,
        SupportTicketPriority::High,
    );
    tenantSupportPageTicket(
        $tenantA,
        $ownerA,
        'Invoice display question',
        SupportTicketStatus::Closed,
        SupportTicketPriority::Normal,
    );

    $tenantB = tenantSupportPageTenant('support-page-list-b');
    $ownerB = tenantSupportPageUser($tenantB, 'support-page-list-b@example.test');
    $foreignTicket = tenantSupportPageTicket($tenantB, $ownerB, 'Private workspace issue');

    $response = $this->actingAs($ownerA)
        ->withSession(['tenant_id' => $tenantA->getKey()])
        ->get(route('support.index', ['tenant' => $tenantA->slug]));

    $response->assertOk()
        ->assertSee('Booking availability problem')
        ->assertSee('Invoice display question')
        ->assertDontSee($foreignTicket->subject)
        ->assertViewHas('totalTickets', 2)
        ->assertViewHas('ticketCounts', function ($counts): bool {
            return $counts->get('open') === 1 && $counts->get('closed') === 1;
        });

    $this->actingAs($ownerA)
        ->withSession(['tenant_id' => $tenantA->getKey()])
        ->get(route('support.show', ['tenant' => $tenantA->slug, 'ticket' => $foreignTicket->getKey()]))
        ->assertNotFound();

    expect($openTicket->tenant_id)->toBe($tenantA->getKey());
});

test('workspace support filters combine search status and priority and preserve query parameters', function (): void {
    app()->setLocale('en');

    $tenant = tenantSupportPageTenant('support-page-filters');
    $owner = tenantSupportPageUser($tenant, 'support-page-filters@example.test');

    $matching = tenantSupportPageTicket(
        $tenant,
        $owner,
        'Morning booking slots missing',
        SupportTicketStatus::Open,
        SupportTicketPriority::High,
    );
    tenantSupportPageTicket(
        $tenant,
        $owner,
        'Invoice question',
        SupportTicketStatus::Open,
        SupportTicketPriority::Normal,
    );
    tenantSupportPageTicket(
        $tenant,
        $owner,
        'Old slot report',
        SupportTicketStatus::Closed,
        SupportTicketPriority::High,
    );

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->get(route('support.index', [
            'tenant' => $tenant->slug,
            'q' => 'Morning',
            'status' => SupportTicketStatus::Open->value,
            'priority' => SupportTicketPriority::High->value,
        ]))
        ->assertOk()
        ->assertSee($matching->subject)
        ->assertDontSee('Invoice question')
        ->assertDontSee('Old slot report')
        ->assertSee('name="q" value="Morning"', false)
        ->assertSee('name="status"', false)
        ->assertSee('name="priority"', false);
});

test('ticket form preserves input errors and creates the initial message atomically on valid submission', function (): void {
    app()->setLocale('en');
    Notification::fake();
    app(PlatformSettings::class)->put(['support_email' => 'support@example.test']);

    $tenant = tenantSupportPageTenant('support-page-create');
    $owner = tenantSupportPageUser($tenant, 'support-page-create@example.test');

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->from(route('support.index', ['tenant' => $tenant->slug]))
        ->post(route('support.store', ['tenant' => $tenant->slug]), [
            'subject' => 'bad',
            'message' => 'tiny',
            'priority' => 'invalid',
        ])
        ->assertRedirect(route('support.index', ['tenant' => $tenant->slug]))
        ->assertSessionHasErrors(['subject', 'message', 'priority']);

    expect(SupportTicket::query()->where('tenant_id', $tenant->getKey())->count())->toBe(0)
        ->and(SupportTicketMessage::query()->count())->toBe(0);

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->post(route('support.store', ['tenant' => $tenant->slug]), [
            'subject' => 'Availability is incorrect',
            'message' => 'The morning appointments do not appear for an active service.',
            'priority' => 'high',
        ])
        ->assertRedirect();

    $ticket = SupportTicket::query()
        ->where('tenant_id', $tenant->getKey())
        ->where('subject', 'Availability is incorrect')
        ->firstOrFail();

    expect($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->priority)->toBe(SupportTicketPriority::High)
        ->and($ticket->messages()->count())->toBe(1)
        ->and($ticket->messages()->firstOrFail()->message)->toBe('The morning appointments do not appear for an active service.');

    Notification::assertSentOnDemand(TenantSupportActivityNotification::class);
});

test('users who can view tickets but cannot create them do not see the ticket form', function (): void {
    app()->setLocale('en');

    $tenant = tenantSupportPageTenant('support-page-read-only');
    $viewer = tenantSupportPageUser($tenant, 'support-page-read-only@example.test', ['support.view']);
    tenantSupportPageTicket($tenant, $viewer, 'Existing support request');

    $this->actingAs($viewer)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->get(route('support.index', ['tenant' => $tenant->slug]))
        ->assertOk()
        ->assertSee('Existing support request')
        ->assertDontSee('name="subject"', false)
        ->assertDontSee('support.store');
});

test('closed tickets do not accept a workspace reply', function (): void {
    app()->setLocale('en');

    $tenant = tenantSupportPageTenant('support-page-closed');
    $owner = tenantSupportPageUser($tenant, 'support-page-closed@example.test');
    $ticket = tenantSupportPageTicket(
        $tenant,
        $owner,
        'Closed request',
        SupportTicketStatus::Closed,
    );

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->from(route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]))
        ->post(route('support.reply', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]), [
            'message' => 'I still need help with this.',
        ])
        ->assertRedirect(route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]))
        ->assertSessionHasErrors('message');

    expect($ticket->messages()->count())->toBe(1)
        ->and($ticket->fresh()->status)->toBe(SupportTicketStatus::Closed);
});

test('workspace replies reopen resolved tickets and notify the configured support inbox', function (): void {
    app()->setLocale('en');
    Notification::fake();
    app(PlatformSettings::class)->put(['support_email' => 'support@example.test']);

    $tenant = tenantSupportPageTenant('support-page-reply');
    $owner = tenantSupportPageUser($tenant, 'support-page-reply@example.test');
    $ticket = tenantSupportPageTicket(
        $tenant,
        $owner,
        'Resolved availability request',
        SupportTicketStatus::Resolved,
    );

    $this->actingAs($owner)
        ->withSession(['tenant_id' => $tenant->getKey()])
        ->from(route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]))
        ->post(route('support.reply', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]), [
            'message' => 'The problem has returned; please reopen this request.',
        ])
        ->assertRedirect(route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]));

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->fresh()->resolved_at)->toBeNull()
        ->and($ticket->messages()->count())->toBe(2);

    Notification::assertSentOnDemand(TenantSupportActivityNotification::class);
});

