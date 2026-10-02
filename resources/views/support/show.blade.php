@extends('layouts.dashboard')

@section('title', $ticket->subject.' — BookResa')
@section('heading', $ticket->subject)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-3"><div><p class="text-sm text-slate-500">#{{ $ticket->id }} · {{ str($ticket->status->value)->headline() }}</p><h2 class="mt-1 text-2xl font-bold">{{ $ticket->subject }}</h2></div><a href="{{ route('support.index', $tenant) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Back') }}</a></div>
    <section class="br-panel p-5">
        <div class="space-y-4">
            @foreach($ticket->messages as $message)
                <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800 {{ $message->author_kind === 'tenant' ? 'me-8 bg-white dark:bg-slate-900' : 'ms-8 bg-indigo-50/50 dark:bg-indigo-950/20' }}">
                    <div class="flex items-center justify-between gap-3"><p class="font-semibold">{{ $message->author?->name ?? __('BookResa Support') }}</p><span class="text-xs text-slate-400">{{ $message->created_at?->format('Y-m-d H:i') }}</span></div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $message->message }}</p>
                </article>
            @endforeach
        </div>
    </section>
    @if(!in_array($ticket->status, [\App\Domain\Support\Enums\SupportTicketStatus::Closed], true))
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Reply') }}</h3>
        <form method="POST" action="{{ route('support.reply', [$tenant, $ticket->id]) }}" class="mt-4 space-y-3">
            @csrf
            <textarea name="message" rows="5" required maxlength="10000" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
            <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white">{{ __('Send reply') }}</button>
        </form>
    </section>
    @endif
</div>
@endsection
