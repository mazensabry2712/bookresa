@extends('layouts.admin')

@section('title', __('Support').' — BookResa')
@section('heading', __('Support'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform operations') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Support') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Review support requests and update their status, priority and internal notes.') }}</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-[1fr_auto_auto_auto] dark:bg-slate-900 dark:ring-slate-800">
            <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search subject or business email') }}"
                class="min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <select name="status" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ __(str((string) $status->value)->headline()->toString()) }}</option>
                @endforeach
            </select>
            <select name="priority" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">{{ __('All priorities') }}</option>
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ __(str((string) $priority->value)->headline()->toString()) }}</option>
                @endforeach
            </select>
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Filter') }}</button>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('Ticket') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Business') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Priority') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Created') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Update') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td class="max-w-sm px-5 py-4 align-top">
                                    <p class="font-semibold">#{{ $ticket->id }} · {{ $ticket->subject }}</p>
                                    <p class="mt-1 line-clamp-3 text-xs text-slate-500">{{ $ticket->message }}</p>
                                    @if ($ticket->requester)
                                        <p class="mt-2 text-xs text-slate-500">{{ $ticket->requester->name }} · {{ $ticket->requester->email }}</p>
                                    @endif
                                    @if ($ticket->admin_notes)
                                        <p class="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-slate-600 dark:bg-slate-950 dark:text-slate-300">
                                            <span class="font-semibold">{{ __('Internal notes') }}:</span> {{ $ticket->admin_notes }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 align-top">
                                    {{ data_get($ticket->tenant->profile?->name, app()->getLocale()) ?? $ticket->tenant->slug }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    {{ __(str($ticket->priority->value)->headline()) }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    {{ __(str($ticket->status->value)->headline()) }}
                                </td>
                                <td class="px-5 py-4 align-top text-slate-500">
                                    {{ $ticket->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <form method="POST" action="{{ route('admin.support.update', $ticket) }}" class="grid gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->value }}" @selected($ticket->status === $status)>{{ __(str((string) $status->value)->headline()->toString()) }}</option>
                                            @endforeach
                                        </select>
                                        <select name="priority" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">
                                            @foreach ($priorities as $priority)
                                                <option value="{{ $priority->value }}" @selected($ticket->priority === $priority)>{{ __(str((string) $priority->value)->headline()->toString()) }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="admin_notes" rows="2" placeholder="{{ __('Internal notes') }}" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
                                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No support tickets found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tickets->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $tickets->links() }}</div>
            @endif
        </section>
    </div>
@endsection
