<?php

namespace App\Http\Controllers\Support;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Notifications\TenantSupportActivityNotification;
use App\Support\AuditLogger;
use App\Support\PlatformSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

final class TenantSupportController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $statusInput = $request->query('status');
        $statusEnum = is_string($statusInput) ? SupportTicketStatus::tryFrom($statusInput) : null;
        $status = $statusEnum->value ?? '';

        $priorityInput = $request->query('priority');
        $priorityEnum = is_string($priorityInput) ? SupportTicketPriority::tryFrom($priorityInput) : null;
        $priority = $priorityEnum->value ?? '';

        $searchInput = $request->query('q');
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 120) : '';

        $baseQuery = SupportTicket::query()->where('tenant_id', $tenant->getKey());

        $ticketCounts = (clone $baseQuery)
            ->reorder()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        $ticketsQuery = (clone $baseQuery)
            ->withCount('messages')
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($priority !== '', fn (Builder $query) => $query->where('priority', $priority))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%');

                    if (ctype_digit($search)) {
                        $nested->orWhere('id', (int) $search);
                    }
                });
            });

        $tickets = $ticketsQuery
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $timezone = (string) ($tenant->profile?->timezone ?: config('app.timezone', 'UTC'));

        try {
            CarbonImmutable::now($timezone);
        } catch (\Throwable) {
            $timezone = (string) (config('app.timezone') ?: 'UTC');
        }

        return view('support.index', [
            'tenant' => $tenant,
            'tickets' => $tickets,
            'statuses' => SupportTicketStatus::cases(),
            'priorities' => SupportTicketPriority::cases(),
            'ticketCounts' => $ticketCounts,
            'totalTickets' => (int) $ticketCounts->sum(),
            'status' => $status,
            'priority' => $priority,
            'search' => $search,
            'hasFilters' => $status !== '' || $priority !== '' || $search !== '',
            'timezone' => $timezone,
        ]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, AuditLogger $audit, PlatformSettings $settings): RedirectResponse
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);

        $ticket = DB::transaction(function () use ($tenant, $request, $validated): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'tenant_id' => $tenant->getKey(),
                'requester_user_id' => $request->user()->getKey(),
                'subject' => trim($validated['subject']),
                'message' => trim($validated['message']),
                'status' => SupportTicketStatus::Open,
                'priority' => SupportTicketPriority::from($validated['priority']),
                'last_replied_at' => now(),
            ]);

            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->getKey(),
                'author_user_id' => $request->user()->getKey(),
                'author_kind' => 'tenant',
                'message' => trim($validated['message']),
            ]);

            return $ticket;
        });

        $audit->log('tenant.support_ticket_created', $ticket, [
            'tenant_id' => (int) $tenant->getKey(),
            'ticket_id' => (int) $ticket->getKey(),
        ]);
        $this->notifySupportInbox($settings, $ticket, (string) $ticket->message, 'created');

        return to_route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()])
            ->with('status', __('app.support_ui.ticket_created'));
    }

    public function show(CurrentTenant $currentTenant, Tenant $tenant, int $ticketId): View
    {
        $activeTenant = $currentTenant->get();
        abort_unless($activeTenant !== null && $tenant->is($activeTenant), 404);

        $ticket = SupportTicket::query()
            ->where('tenant_id', $activeTenant->getKey())
            ->with('messages.author')
            ->findOrFail($ticketId);

        $timezone = (string) ($activeTenant->profile?->timezone ?: config('app.timezone', 'UTC'));

        try {
            CarbonImmutable::now($timezone);
        } catch (\Throwable) {
            $timezone = (string) (config('app.timezone') ?: 'UTC');
        }

        return view('support.show', [
            'tenant' => $activeTenant,
            'ticket' => $ticket,
            'timezone' => $timezone,
        ]);
    }

    public function reply(Request $request, CurrentTenant $currentTenant, Tenant $tenant, int $ticketId, AuditLogger $audit, PlatformSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:10000'],
        ]);

        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $ticket = SupportTicket::query()
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($ticketId);

        if ($ticket->status === SupportTicketStatus::Closed) {
            return back()->withErrors([
                'message' => __('app.support_ui.closed_reply_help'),
            ])->withInput();
        }

        DB::transaction(function () use ($ticket, $request, $validated): void {
            SupportTicketMessage::query()->create([
                'support_ticket_id' => $ticket->getKey(),
                'author_user_id' => $request->user()->getKey(),
                'author_kind' => 'tenant',
                'message' => trim($validated['message']),
            ]);

            $ticket->forceFill([
                'status' => SupportTicketStatus::Open,
                'last_replied_at' => now(),
                'resolved_at' => null,
            ])->save();
        });

        $audit->log('tenant.support_reply_sent', $ticket, [
            'tenant_id' => (int) $tenant->getKey(),
            'ticket_id' => (int) $ticket->getKey(),
        ]);
        $this->notifySupportInbox($settings, $ticket, trim($validated['message']), 'reply');

        return back()->with('status', __('app.support_ui.reply_sent'));
    }

    private function notifySupportInbox(
        PlatformSettings $settings,
        SupportTicket $ticket,
        string $message,
        string $activity,
    ): void {
        $supportEmail = trim((string) $settings->get('support_email', ''));

        if ($supportEmail === '' || filter_var($supportEmail, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        try {
            Notification::route('mail', $supportEmail)->notify(
                new TenantSupportActivityNotification($ticket, $message, $activity),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
