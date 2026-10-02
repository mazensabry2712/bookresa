@extends('layouts.admin')

@section('title', __('Support ticket').' — BookResa')
@section('heading', __('Support ticket'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm text-slate-500">#{{ $ticket->id }} · {{ $ticket->tenant?->slug }}</p><h2 class="mt-1 text-2xl font-bold">{{ $ticket->subject }}</h2><p class="mt-1 text-sm text-slate-500">{{ $ticket->requester?->name }} · {{ $ticket->requester?->email }}</p></div>
        <a href="{{ route('admin.support.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Back') }}</a>
    </div>
    <section class="br-panel p-5">
        <div class="space-y-4">
            @forelse($ticket->messages as $message)
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800 {{ $message->author_kind === 'platform' ? 'ms-8 bg-indigo-50/50 dark:bg-indigo-950/20' : 'me-8 bg-white dark:bg-slate-900' }}">
                    <div class="flex items-center justify-between gap-3"><p class="font-semibold">{{ $message->author?->name ?? __('System') }}</p><span class="text-xs text-slate-400">{{ $message->created_at?->format('Y-m-d H:i') }}</span></div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $message->message }}</p>
                </article>
            @empty
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800"><p class="text-sm text-slate-600 dark:text-slate-300">{{ $ticket->message }}</p></article>
            @endforelse
        </div>
    </section>
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Reply to customer') }}</h3>
        <form method="POST" action="{{ route('admin.support.reply', $ticket->id) }}" class="mt-4 space-y-3">
            @csrf
            <textarea name="message" rows="6" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
            <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white">{{ __('Send reply') }}</button>
        </form>
    </section>
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Ticket controls') }}</h3>
        <form method="POST" action="{{ route('admin.support.update', $ticket->id) }}" class="mt-4 grid gap-4 md:grid-cols-3">
            @csrf @method('PATCH')
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ str($status->value)->headline() }}</option>@endforeach</select>
            <select name="priority" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">@foreach($priorities as $priority)<option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ str($priority->value)->headline() }}</option>@endforeach</select>
            <input name="admin_notes" value="{{ $ticket->admin_notes }}" placeholder="{{ __('Internal notes') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900 md:col-span-3">{{ __('Save controls') }}</button>
        </form>
    </section>
</div>
@endsection
