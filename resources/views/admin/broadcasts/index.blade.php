@extends('layouts.admin')

@section('title', __('Broadcasts').' — BookResa')
@section('heading', __('Broadcasts'))

@section('content')
<div class="space-y-6">
    <div><p class="text-sm text-slate-500">{{ __('Platform communication') }}</p><h2 class="mt-1 text-2xl font-bold">{{ __('Broadcasts') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Send database notifications to every active workspace member or one workspace.') }}</p></div>
    <section class="br-panel p-5">
        <form method="POST" action="{{ route('admin.broadcasts.store') }}" class="grid gap-4 md:grid-cols-2">
            @csrf
            <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Workspace target') }}</span><select name="tenant_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"><option value="">{{ __('All active workspaces') }}</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ data_get($tenant->profile?->name, app()->getLocale()) ?? $tenant->slug }}</option>@endforeach</select></label>
            <div></div>
            <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('English title') }}</span><input name="title_en" required maxlength="180" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
            <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Arabic title') }}</span><input name="title_ar" required maxlength="180" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
            <label class="block text-sm md:col-span-2"><span class="mb-1 block font-medium">{{ __('English message') }}</span><textarea name="message_en" required maxlength="10000" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-3 dark:border-slate-700 dark:bg-slate-950"></textarea></label>
            <label class="block text-sm md:col-span-2"><span class="mb-1 block font-medium">{{ __('Arabic message') }}</span><textarea name="message_ar" required maxlength="10000" rows="4" dir="rtl" class="w-full rounded-xl border border-slate-300 px-3 py-3 dark:border-slate-700 dark:bg-slate-950"></textarea></label>
            <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white md:col-span-2">{{ __('Queue broadcast') }}</button>
        </form>
    </section>
    <section class="br-panel overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800"><tr><th class="px-5 py-3 text-start">#</th><th class="px-5 py-3 text-start">{{ __('Target') }}</th><th class="px-5 py-3 text-start">{{ __('Title') }}</th><th class="px-5 py-3 text-start">{{ __('Status') }}</th><th class="px-5 py-3 text-start">{{ __('Recipients') }}</th><th class="px-5 py-3 text-start">{{ __('Sent') }}</th></tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800">
    @forelse($broadcasts as $broadcast)<tr><td class="px-5 py-4">{{ $broadcast->id }}</td><td class="px-5 py-4">{{ $broadcast->tenant?->slug ?? __('All workspaces') }}</td><td class="px-5 py-4 font-semibold">{{ $broadcast->title_en }}</td><td class="px-5 py-4">{{ str($broadcast->status)->headline() }}</td><td class="px-5 py-4">{{ number_format($broadcast->recipients_count) }}</td><td class="px-5 py-4 text-slate-500">{{ $broadcast->sent_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>@empty<tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">{{ __('No broadcasts yet.') }}</td></tr>@endforelse
    </tbody></table></div>@if($broadcasts->hasPages())<div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $broadcasts->links() }}</div>@endif</section>
</div>
@endsection
