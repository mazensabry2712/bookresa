<?php

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function supportTenant(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'slug' => $slug,
        'status' => TenantStatus::Active,
    ]);

    app(CurrentTenant::class)->set($tenant);

    BusinessProfile::query()->create([
        'tenant_id' => $tenant->id,
        'name' => ['en' => ucfirst($slug), 'ar' => ucfirst($slug)],
        'email' => $slug.'@example.com',
        'timezone' => 'Africa/Cairo',
    ]);

    return $tenant;
}

test('platform admin can review and update support tickets', function (): void {
    $admin = User::factory()->create(['email' => 'support-admin@example.com']);
    PlatformAdmin::query()->create([
        'user_id' => $admin->id,
        'is_active' => true,
    ]);

    $tenant = supportTenant('support-business');
    $ticket = SupportTicket::query()->create([
        'tenant_id' => $tenant->id,
        'requester_user_id' => $admin->id,
        'subject' => 'Booking availability issue',
        'message' => 'Some slots are not appearing.',
        'status' => SupportTicketStatus::Open,
        'priority' => SupportTicketPriority::High,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.support.index'))
        ->assertOk()
        ->assertSee('Booking availability issue');

    $this->actingAs($admin)
        ->patch(route('admin.support.update', $ticket), [
            'status' => SupportTicketStatus::Resolved->value,
            'priority' => SupportTicketPriority::Urgent->value,
            'admin_notes' => 'Verified and resolved.',
        ])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Resolved)
        ->and($ticket->fresh()->priority)->toBe(SupportTicketPriority::Urgent)
        ->and($ticket->fresh()->admin_notes)->toBe('Verified and resolved.')
        ->and($ticket->fresh()->resolved_at)->not->toBeNull();
});

test('non platform admin cannot access support management', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.support.index'))
        ->assertForbidden();
});
