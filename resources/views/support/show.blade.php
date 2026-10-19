@extends('layouts.dashboard')

@section('title', $ticket->subject.' — '.__('app.support_ui.title'))
@section('heading', $ticket->subject)

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
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/30 dark:text-emerald-200">{{ session('status') }}</div>
        @endif

        <section class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <a href="{{ route('support.index', ['tenant' => $tenant->slug]) }}" class="inline-flex text-sm font-semibold text-brand-indigo hover:underline">← {{ __('app.support_ui.back_to_tickets') }}</a>
                <p class="mt-3 text-xs font-mono text-slate-500">{{ __('app.support_ui.ticket_number', ['id' => $ticket->getKey()]) }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $ticket->subject }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('app.support_ui.created') }}: {{ $ticket->created_at?->copy()->timezone($timezone)->locale(app()->getLocale())->translatedFormat('d M Y, H:i') ?? '—' }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-bold {{ $statusClasses[$ticket->status->value] ?? $statusClasses['open'] }}">{{ $statusLabels[$ticket->status->value] ?? $ticket->status->value }}</span>
                <span class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-bold {{ $priorityClasses[$ticket->priority->value] ?? $priorityClasses['normal'] }}">{{ $priorityLabels[$ticket->priority->value] ?? $ticket->priority->value }}</span>
            </div>
        </section>

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

        <section class="br-panel p-4 sm:p-6">
            <div class="mb-5">
                <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('app.support_ui.conversation') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('app.support_ui.conversation_help') }}</p>
            </div>

            <div class="space-y-4">
                @forelse ($ticket->messages as $message)
                    @php $isTenantMessage = $message->author_kind === 'tenant'; @endphp
                    <article class="rounded-2xl border p-4 sm:p-5 {{ $isTenantMessage ? 'me-3 sm:me-12 border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' : 'ms-3 sm:ms-12 border-indigo-200 bg-indigo-50/50 dark:border-indigo-900/70 dark:bg-indigo-950/20' }}">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-slate-900 dark:text-white">{{ $isTenantMessage ? ($message->author?->name ?? __('app.support_ui.workspace_member')) : __('app.support_ui.bookresa_support') }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $isTenantMessage ? __('app.support_ui.you_sent') : __('app.support_ui.support_replied') }}</p>
                            </div>
                            <time class="text-xs text-slate-400">{{ $message->created_at?->copy()->timezone($timezone)->locale(app()->getLocale())->translatedFormat('d M Y, H:i') ?? '—' }}</time>
                        </div>
                        <p class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-slate-700 dark:text-slate-200">{{ $message->message }}</p>
                    </article>
                @empty
                    <p class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:bg-slate-950">{{ __('app.support_ui.no_messages') }}</p>
                @endforelse
            </div>
        </section>

        @if ($ticket->status === \App\Domain\Support\Enums\SupportTicketStatus::Closed)
            <section class="br-panel p-5">
                <p class="font-semibold text-slate-800 dark:text-slate-200">{{ __('app.support_ui.ticket_closed') }}</p>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.support_ui.closed_reply_help') }}</p>
            </section>
        @elseif (auth()->user()?->can('support.reply'))
            <section class="br-panel p-5 sm:p-6">
                <h3 class="text-lg font-bold text-slate-950 dark:text-white">{{ __('app.support_ui.reply') }}</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.support_ui.reply_help') }}</p>
                <form method="POST" action="{{ route('support.reply', ['tenant' => $tenant->slug, 'ticket' => $ticket->getKey()]) }}" class="mt-4 space-y-3">
                    @csrf
                    <label for="support-reply" class="sr-only">{{ __('app.support_ui.message') }}</label>
                    <textarea id="support-reply" name="message" rows="5" required minlength="2" maxlength="10000"
                              class="w-full resize-y rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm leading-6 text-slate-900 placeholder:text-slate-400 focus:border-brand-indigo focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:ring-indigo-950"
                              placeholder="{{ __('app.support_ui.reply_placeholder') }}">{{ old('message') }}</textarea>
                    @error('message') <p class="text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">{{ __('app.support_ui.send_reply') }}</button>
                        <p class="text-xs text-slate-500">{{ __('app.support_ui.reply_reopens_help') }}</p>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
