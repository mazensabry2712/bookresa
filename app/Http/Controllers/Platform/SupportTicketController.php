<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use App\Notifications\SupportReplyNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SupportTicketController
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->input('status'));
        $priority = trim((string) $request->input('priority'));
        $search = trim((string) $request->input('search'));

        $tickets = SupportTicket::query()
            ->with([
                'tenant' => fn ($query) => $query->withoutGlobalScopes()->with('profile'),
                'requester',
            ])
            ->withCount('messages')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhereHas('tenant.profile', function ($profile) use ($search): void {
                            $profile->withoutGlobalScopes();
                            $profile->where('email', 'like', '%'.$search.'%');
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support.index', [
            'tickets' => $tickets,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function show(int $ticketId): View
    {
        $ticket = SupportTicket::withoutGlobalScopes()
            ->with([
                'tenant' => fn ($query) => $query->withoutGlobalScopes()->with('profile'),
                'requester',
                'messages.author',
            ])
            ->findOrFail($ticketId);

        return view('admin.support.show', [
            'ticket' => $ticket,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
        ]);
    }

    public function update(Request $request, int $ticket, AuditLogger $auditLogger): RedirectResponse
    {
        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($ticket);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(SupportTicketStatus::cases(), 'value'))],
            'priority' => ['required', 'in:'.implode(',', array_column(SupportTicketPriority::cases(), 'value'))],
            'admin_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $status = SupportTicketStatus::from($validated['status']);
        $priority = SupportTicketPriority::from($validated['priority']);
        $tenant = $ticket->tenant()->withoutGlobalScopes()->firstOrFail();

        app(CurrentTenant::class)->run($tenant, function () use ($ticket, $status, $validated, $auditLogger): void {
            $ticket->update([
                'status' => $status,
                'priority' => $priority,
                'admin_notes' => $validated['admin_notes'] ?? null,
                'resolved_at' => in_array($status, [
                    SupportTicketStatus::Resolved,
                    SupportTicketStatus::Closed,
                ], true) ? now() : null,
            ]);

            $auditLogger->log('Platform support ticket updated', $ticket, [
                'status' => $status->value,
                'priority' => $priority->value,
            ]);
        });

        return back()->with('status', __('Support ticket updated successfully.'));
    }

    public function reply(Request $request, int $ticketId, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $ticket = SupportTicket::withoutGlobalScopes()->findOrFail($ticketId);
        $tenant = $ticket->tenant()->withoutGlobalScopes()->firstOrFail();

        app(CurrentTenant::class)->run($tenant, function () use ($ticket, $validated, $request, $audit): void {
            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->getKey(),
                'author_user_id' => $request->user()->getKey(),
                'author_kind' => 'platform',
                'message' => $validated['message'],
            ]);

            $ticket->forceFill([
                'status' => SupportTicketStatus::InProgress,
                'last_replied_at' => now(),
                'resolved_at' => null,
            ])->save();

            $audit->log('platform.support_reply_sent', $ticket, [
                'tenant_id' => (int) $ticket->tenant_id,
                'ticket_id' => (int) $ticket->getKey(),
            ]);

            $requester = $ticket->requester()->first();
            $requester?->notify(new SupportReplyNotification($ticket, $validated['message']));
        });

        return back()->with('status', __('Support reply added successfully.'));
    }
}
