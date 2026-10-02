@extends('layouts.admin')

@section('title', __('System Health').' — BookResa')
@section('heading', __('System Health'))

@section('content')
<div class="space-y-6">
    <div><p class="text-sm text-slate-500">{{ $appEnvironment }} · PHP {{ $phpVersion }}</p><h2 class="mt-1 text-2xl font-bold">{{ __('System Health') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('Operational visibility for the BookResa platform runtime.') }}</p></div>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($checks as $name => $ok)
            <article class="br-panel p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ str($name)->replace('_',' ')->headline() }}</p><p class="mt-2 text-2xl font-bold {{ $ok ? 'text-emerald-600' : 'text-rose-600' }}">{{ $ok ? __('Healthy') : __('Attention') }}</p></article>
        @endforeach
    </section>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach([['Queued jobs',$queuedJobs],['Failed jobs',$failedJobs],['Sessions',$sessions],['Open tickets',$openTickets],['Pending broadcasts',$pendingBroadcasts]] as [$label,$value])
            <article class="br-panel p-5"><p class="text-xs text-slate-500">{{ __($label) }}</p><p class="mt-2 text-2xl font-bold">{{ number_format($value) }}</p></article>
        @endforeach
    </section>
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Runtime configuration') }}</h3>
        <div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-3 text-sm">
            <div><span class="text-slate-500">Cache</span><p class="font-semibold">{{ $cacheDriver }}</p></div>
            <div><span class="text-slate-500">Queue</span><p class="font-semibold">{{ $queueDriver }}</p></div>
            <div><span class="text-slate-500">Session</span><p class="font-semibold">{{ $sessionDriver }}</p></div>
            <div><span class="text-slate-500">Timezone</span><p class="font-semibold">{{ $timezone }}</p></div>
            <div><span class="text-slate-500">PHP</span><p class="font-semibold">{{ $phpVersion }}</p></div>
        </div>
    </section>
    <section class="br-panel p-5">
        <div class="flex items-center justify-between gap-3"><h3 class="font-bold">{{ __('Recent backups') }}</h3><span class="text-xs text-slate-500">{{ __('Up to 10 local backup files') }}</span></div>
        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-800"><tr><th class="px-3 py-3 text-start">{{ __('File') }}</th><th class="px-3 py-3 text-start">{{ __('Size') }}</th><th class="px-3 py-3 text-start">{{ __('Modified') }}</th></tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            @forelse($backups as $backup)<tr><td class="px-3 py-3 font-mono text-xs">{{ $backup['name'] }}</td><td class="px-3 py-3">{{ number_format($backup['size']/1024,1) }} KB</td><td class="px-3 py-3 text-slate-500">{{ $backup['modified_at'] }}</td></tr>@empty<tr><td colspan="3" class="px-3 py-8 text-center text-slate-500">{{ __('No local backups detected.') }}</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
