@extends('layouts.dashboard')

@section('title', __('app.support_ui.title').' — BookResa')
@section('heading', __('app.support_ui.title'))

@section('content')
    @php
        $statusClasses = [
            'open' => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/70 dark:bg-sky-950/30 dark:text-sky-200',
            'in_progress' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-200',
            'resolved' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200',
            'closed' => 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ];
        $priorityClasses = [
            'low' => 'text-slate-500 dark:text-slate-400',
            'normal' => 'text-slate-600 dark:text-slate-300',
            'high' => 'text-amber-700 dark:text-amber-300',
            'urgent' => 'text-rose-700 dark:text-rose-300',
        ];
        $statusLabels = [
            'open' => __('app.support_ui.status_open'),
            'in_progress' => __('app.support_ui.status_in_progress'),
            'resolved' => __('app.support_ui.status_resolved'),
            'closed' => __('app.support_ui.status_closed'),
        ];
        $priorityLabels = [
            'low' => __('app.support_ui.priority_low'),
            'normal' => __('app.support_ui.priority_normal'),
            'high' => __('app.support_ui.priority_high'),
            'urgent' => __('app.support_ui.priority_urgent'),
        ];
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-200">
                <p class="font-bold">{{ __('app.support_ui.fix_form_errors') }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.support_ui.title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.support_ui.page_help') }}</p>
            </div>
            @can('support.create')
                <a href="#support-ticket-form" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
                    {{ __('app.support_ui.open_ticket') }}
                </a>
            @endcan
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="br-panel p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('app.support_ui.total_tickets') }}</p>
                <p class="mt-2 text-2xl font-extrabold tabular-nums text-slate-950 dark:text-white">{{ number_format($totalTickets) }}</p>
            </article>
            <article class="br-panel p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('app.support_ui.status_open') }}</p>
                <p class="mt-2 text-2xl font-extrabold tabular-nums text-slate-950 dark:text-white">{{ number_format($ticketCounts->get('open', 0)) }}</p>
            </article>
            <article class="br-panel p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('app.support_ui.status_in_progress') }}</p>
                <p class="mt-2 text-2xl font-extrabold tabular-nums text-slate-950 dark:text-white">{{ number_format($ticketCounts->get('in_progress', 0)) }}</p>
            </article>
            <article class="br-panel p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ __('app.support_ui.status_resolved_closed') }}</p>
                <p class="mt-2 text-2xl font-extrabold tabular-nums text-slate-950 dark:text-white">{{ number_format($ticketCounts->get('resolved', 0) + $ticketCounts->get('closed', 0)) }}</p>
            </article>
        </section>

        @can('support.create')
            <section id="support-ticket-form" class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('app.support_ui.new_ticket') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.support_ui.new_ticket_help') }}</p>
                </div>

                <form method="POST" action="{{ route('support.store', ['tenant' => $tenant->slug]) }}" class="mt-5 grid gap-5 md:grid-cols-3">
                    @csrf

                    <div class="md:col-span-2">
                        <label for="support-subject" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('app.support_ui.subject') }}</label>
                        <input id="support-subject" name="subject" type="text" required minlength="5" maxlength="180" value="{{ old('subject') }}" autocomplete="off"
                               aria-describedby="support-subject-help"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-indigo focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950"
                               placeholder="{{ __('app.support_ui.subject_placeholder') }}">
                        <p id="support-subject-help" class="mt-1.5 text-xs leading-5 text-slate-500">{{ __('app.support_ui.subject_help') }}</p>
                        @error('subject') <p class="mt-1.5 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="support-priority" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('app.support_ui.priority') }}</label>
                        <select id="support-priority" name="priority" required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 focus:border-brand-indigo focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="normal" @selected(old('priority', 'normal') === 'normal')>{{ $priorityLabels['normal'] }}</option>
                            <option value="low" @selected(old('priority') === 'low')>{{ $priorityLabels['low'] }}</option>
                            <option value="high" @selected(old('priority') === 'high')>{{ $priorityLabels['high'] }}</option>
                            <option value="urgent" @selected(old('priority') === 'urgent')>{{ $priorityLabels['urgent'] }}</option>
                        </select>
                        <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ __('app.support_ui.priority_help') }}</p>
                        @error('priority') <p class="mt-1.5 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label for="support-message" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('app.support_ui.message') }}</label>
                        <textarea id="support-message" name="message" required minlength="10" maxlength="10000" rows="6"
                                  aria-describedby="support-message-help"
                                  class="w-full resize-y rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 placeholder:text-slate-400 focus:border-brand-indigo focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950"
                                  placeholder="{{ __('app.support_ui.message_placeholder') }}">{{ old('message') }}</textarea>
                        <div class="mt-1.5 flex flex-col gap-1 text-xs leading-5 text-slate-500 sm:flex-row sm:items-start sm:justify-between">
                            <p id="support-message-help">{{ __('app.support_ui.message_help') }}</p>
                            <p>{{ __('app.support_ui.message_limit') }}</p>
                        </div>
                        @error('message') <p class="mt-1.5 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 md:col-span-3">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">
                            {{ __('app.support_ui.submit_ticket') }}
                        </button>
                        <p class="text-xs leading-5 text-slate-500">{{ __('app.support_ui.private_help') }}</p>
                    </div>
                </form>
            </section>
        @endcan

        <section class="br-panel overflow-hidden">
            <div class="border-b border-slate-200 p-4 dark:border-slate-800 sm:p-5">
                <h3 class="font-bold text-slate-950 dark:text-white">{{ __('app.support_ui.your_tickets') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('app.support_ui.your_tickets_help') }}</p>

                <form method="GET" action="{{ route('support.index', ['tenant' => $tenant->slug]) }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <label class="block xl:col-span-2">
                        <span class="mb-1.5 block text-xs font-bold text-slate-500">{{ __('app.support_ui.search') }}</span>
                        <input type="search" name="q" value="{{ $search }}" maxlength="120"
                               placeholder="{{ __('app.support_ui.search_placeholder') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-500">{{ __('app.support_ui.status') }}</span>
                        <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="">{{ __('app.support_ui.all_statuses') }}</option>
                            @foreach ($statuses as $ticketStatus)
                                <option value="{{ $ticketStatus->value }}" @selected($status === $ticketStatus->value)>{{ $statusLabels[$ticketStatus->value] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-bold text-slate-500">{{ __('app.support_ui.priority') }}</span>
                        <select name="priority" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="">{{ __('app.support_ui.all_priorities') }}</option>
                            @foreach ($priorities as $ticketPriority)
                                <option value="{{ $ticketPriority->value }}" @selected($priority === $ticketPriority->value)>{{ $priorityLabels[$ticketPriority->value] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-4">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">{{ __('app.support_ui.apply_filters') }}</button>
                        <a href="{{ route('support.index', ['tenant' => $tenant->slug]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ __('app.support_ui.clear_filters') }}</a>
                        <p class="text-xs text-slate-500 md:ms-auto">{{ __('app.support_ui.results_count', ['count' => number_format($tickets->total())]) }}</p>
                    </div>
                </form>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950/60">
                        <tr class="text-start text-xs font-bold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">{{ __('app.support_ui.subject') }}</th>
                            <th class="px-5 py-3">{{ __('app.support_ui.status') }}</th>
                            <th class="px-5 py-3">{{ __('app.support_ui.priority') }}</th>
                            <th class="px-5 py-3">{{ __('app.support_ui.messages') }}</th>
                            <th class="px-5 py-3">{{ __('app.support_ui.last_activity') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($tickets as $ticket)
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/30">
                                <td class="whitespace-nowrap px-5 py-4 text-xs font-mono text-slate-500">#{{ $ticket->id }}</td>
                                <td class="min-w-52 px-5 py-4">
                                    <a class="font-semibold text-brand-indigo hover:underline" href="{{ route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]) }}">{{ $ticket->subject }}</a>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('app.support_ui.created') }}: {{ $ticket->created_at?->copy()->timezone($timezone)->locale(app()->getLocale())->translatedFormat('d M Y, H:i') ?? '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$ticket->status->value] ?? $statusClasses['open'] }}">{{ $statusLabels[$ticket->status->value] ?? $ticket->status->value }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold {{ $priorityClasses[$ticket->priority->value] ?? $priorityClasses['normal'] }}">{{ $priorityLabels[$ticket->priority->value] ?? $ticket->priority->value }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ number_format($ticket->messages_count) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ $ticket->last_replied_at?->copy()->timezone($timezone)->locale(app()->getLocale())->translatedFormat('d M Y, H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <p class="font-bold text-slate-950 dark:text-white">{{ $hasFilters ? __('app.support_ui.no_filtered_tickets') : __('app.support_ui.no_tickets') }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('app.support_ui.no_filtered_tickets_help') : __('app.support_ui.no_tickets_help') }}</p>
                                    @if ($hasFilters)
                                        <a href="{{ route('support.index', ['tenant' => $tenant->slug]) }}" class="mt-3 inline-flex text-sm font-bold text-brand-indigo hover:underline">{{ __('app.support_ui.clear_filters') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-200 md:hidden">
                @forelse ($tickets as $ticket)
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs text-slate-400">#{{ $ticket->id }}</p>
                                <a class="mt-1 inline-block font-bold text-brand-indigo hover:underline" href="{{ route('support.show', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]) }}">{{ $ticket->subject }}</a>
                            </div>
                            <span class="shrink-0 inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClasses[$ticket->status->value] ?? $statusClasses['open'] }}">{{ $statusLabels[$ticket->status->value] ?? $ticket->status->value }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                            <span class="font-semibold {{ $priorityClasses[$ticket->priority->value] ?? $priorityClasses['normal'] }}">{{ $priorityLabels[$ticket->priority->value] ?? $ticket->priority->value }}</span>
                            <span>{{ __('app.support_ui.messages') }}: {{ number_format($ticket->messages_count) }}</span>
                            <span>{{ $ticket->last_replied_at?->copy()->timezone($timezone)->locale(app()->getLocale())->translatedFormat('d M Y, H:i') ?? '—' }}</span>
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-bold text-slate-950 dark:text-white">{{ $hasFilters ? __('app.support_ui.no_filtered_tickets') : __('app.support_ui.no_tickets') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $hasFilters ? __('app.support_ui.no_filtered_tickets_help') : __('app.support_ui.no_tickets_help') }}</p>
                        @if ($hasFilters)
                            <a href="{{ route('support.index', ['tenant' => $tenant->slug]) }}" class="mt-3 inline-flex text-sm font-bold text-brand-indigo hover:underline">{{ __('app.support_ui.clear_filters') }}</a>
                        @endif
                    </div>
                @endforelse
            </div>

            @if ($tickets->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $tickets->links() }}</div>
            @endif
        </section>
    </div>
@endsection
