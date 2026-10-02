@extends('layouts.dashboard')

@section('title', __('Support').' — BookResa')
@section('heading', __('Support'))

@section('content')
<div class="space-y-6">
    <section class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p><h2 class="mt-1 text-2xl font-bold">{{ __('Support') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Contact BookResa support and follow your requests from one thread.') }}</p></div>
    </section>
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Open a support ticket') }}</h3>
        <form method="POST" action="{{ route('support.store', $tenant) }}" class="mt-4 grid gap-4 md:grid-cols-3">
            @csrf
            <input name="subject" required maxlength="180" placeholder="{{ __('Subject') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 md:col-span-2">
            <select name="priority" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                @foreach(['normal','low','high','urgent'] as $priority)<option value="{{ $priority }}">{{ str($priority)->headline() }}</option>@endforeach
            </select>
            <textarea name="message" required rows="5" maxlength="10000" placeholder="{{ __('Describe your issue...') }}" class="rounded-xl border border-slate-300 px-3 py-3 text-sm dark:border-slate-700 dark:bg-slate-950 md:col-span-3"></textarea>
            <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white md:col-span-3">{{ __('Create ticket') }}</button>
        </form>
    </section>
    <section class="br-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800"><tr><th class="px-5 py-3 text-start">#</th><th class="px-5 py-3 text-start">{{ __('Subject') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th><th class="px-5 py-3 text-start">{{ __('Priority') }}</th><th class="px-5 py-3 text-start">{{ __('Messages') }}</th></tr></thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse($tickets as $ticket)
                    <tr><td class="px-5 py-4">{{ $ticket->id }}</td><td class="px-5 py-4"><a class="font-semibold text-brand-indigo hover:underline" href="{{ route('support.show', [$tenant, $ticket->id]) }}">{{ $ticket->subject }}</a></td><td class="px-5 py-4">{{ str($ticket->status->value)->headline() }}</td><td class="px-5 py-4">{{ str($ticket->priority->value)->headline() }}</td><td class="px-5 py-4">{{ $ticket->messages_count }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No support tickets yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())<div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $tickets->links() }}</div>@endif
    </section>
</div>
@endsection
