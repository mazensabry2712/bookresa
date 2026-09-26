<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Support\AuditLogger;
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
            ->with(['tenant.profile', 'requester'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($priority !== '', fn ($query) => $query->where('priority', $priority))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhereHas('tenant.profile', function ($profile) use ($search): void {
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

    public function update(Request $request, SupportTicket $ticket, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(SupportTicketStatus::cases(), 'value'))],
            'priority' => ['required', 'in:'.implode(',', array_column(SupportTicketPriority::cases(), 'value'))],
            'admin_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $status = SupportTicketStatus::from($validated['status']);

        $ticket->update([
            'status' => $status,
            'priority' => SupportTicketPriority::from($validated['priority']),
            'admin_notes' => $validated['admin_notes'] ?? null,
            'resolved_at' => in_array($status, [
                SupportTicketStatus::Resolved,
                SupportTicketStatus::Closed,
            ], true) ? now() : null,
        ]);

        $auditLogger->log('Platform support ticket updated', $ticket, [
            'status' => $status->value,
            'priority' => $ticket->priority->value,
        ]);

        return back()->with('status', __('Support ticket updated successfully.'));
    }
}
