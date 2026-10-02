<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TenantSupportController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        return view('support.index', [
            'tenant' => $tenant,
            'tickets' => SupportTicket::query()
                ->where('tenant_id', $tenant->getKey())
                ->withCount('messages')
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $ticket = SupportTicket::query()->create([
            'tenant_id' => $tenant->getKey(),
            'requester_user_id' => $request->user()->getKey(),
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => SupportTicketStatus::Open,
            'priority' => SupportTicketPriority::from($validated['priority']),
            'last_replied_at' => now(),
        ]);

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->getKey(),
            'author_user_id' => $request->user()->getKey(),
            'author_kind' => 'tenant',
            'message' => $validated['message'],
        ]);

        $audit->log('tenant.support_ticket_created', $ticket, [
            'tenant_id' => (int) $tenant->getKey(),
            'ticket_id' => (int) $ticket->getKey(),
        ]);

        return to_route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket])
            ->with('status', __('Support ticket created successfully.'));
    }

    public function show(CurrentTenant $currentTenant, int $ticketId): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $ticket = SupportTicket::query()
            ->where('tenant_id', $tenant->getKey())
            ->with('messages.author')
            ->findOrFail($ticketId);

        return view('support.show', [
            'tenant' => $tenant,
            'ticket' => $ticket,
            'priorities' => SupportTicketPriority::cases(),
            'statuses' => SupportTicketStatus::cases(),
        ]);
    }

    public function reply(Request $request, CurrentTenant $currentTenant, int $ticketId, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $ticket = SupportTicket::query()
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($ticketId);

        if ($ticket->status === SupportTicketStatus::Closed) {
            abort(422, __('Closed support tickets cannot receive new replies.'));
        }

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->getKey(),
            'author_user_id' => $request->user()->getKey(),
            'author_kind' => 'tenant',
            'message' => $validated['message'],
        ]);

        $ticket->forceFill([
            'status' => SupportTicketStatus::Open,
            'last_replied_at' => now(),
            'resolved_at' => null,
        ])->save();

        $audit->log('tenant.support_reply_sent', $ticket, [
            'tenant_id' => (int) $tenant->getKey(),
            'ticket_id' => (int) $ticket->getKey(),
        ]);

        return back()->with('status', __('Reply sent successfully.'));
    }
}
