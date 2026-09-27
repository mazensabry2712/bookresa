@php
    $workspaceMemberships = auth()->user()
        ->tenantMemberships()
        ->where('status', AppDomainTenantEnumsMembershipStatus::Active->value)
        ->whereHas('tenant', fn ($query) => $query->where('status', AppDomainTenantEnumsTenantStatus::Active->value))
        ->with('tenant.profile')
        ->orderByDesc('is_primary')
        ->orderBy('id')
        ->get();

    $workspaceName = static fn ($workspace): string => (string) (
        data_get($workspace->profile?->name, app()->getLocale())
        ?? data_get($workspace->profile?->name, 'en')
        ?? data_get($workspace->profile?->name, 'ar')
        ?? $workspace->slug
    );
@endphp

<div class="relative br-workspace-switcher" data-workspace-switcher>
    <button type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-start shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            data-workspace-switcher-toggle
            aria-expanded="false"
            aria-haspopup="true">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-soft dark:bg-slate-800">
            <img src="{{ asset('logo.png') }}"
                 alt=""
                 class="h-full w-full object-contain p-1 dark:hidden">
            <img src="{{ asset('logodark.png') }}"
                 alt=""
                 class="hidden h-full w-full object-contain p-1 dark:block">
        </span>
        <span class="min-w-0 flex-1 br-sidebar-label">
            <span class="block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.workspace') }}</span>
            <span class="mt-0.5 block truncate text-sm font-bold text-slate-900 dark:text-white">{{ $workspaceName($tenant) }}</span>
        </span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 br-sidebar-label" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/>
        </svg>
    </button>

    <div class="absolute inset-inline-start-0 top-full z-[70] mt-2 hidden w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900"
         data-workspace-switcher-menu>
        <div class="px-3 pb-2 pt-1">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.switch_workspace') }}</p>
        </div>

        <div class="space-y-1">
            @foreach ($workspaceMemberships as $membership)
                @php
                    $workspace = $membership->tenant;
                    $isCurrent = $workspace?->is($tenant);
                @endphp

                @if ($workspace)
                    <a href="{{ route('dashboard', ['tenant' => $workspace->slug]) }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $isCurrent ? 'bg-indigo-50 text-brand-indigo dark:bg-indigo-950/40 dark:text-indigo-300' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800' }}"
                       @if ($isCurrent) aria-current="page" @endif>
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-brand-soft dark:bg-slate-800">
                            <img src="{{ asset('logo.png') }}" alt="" class="h-full w-full object-contain p-1 dark:hidden">
                            <img src="{{ asset('logodark.png') }}" alt="" class="hidden h-full w-full object-contain p-1 dark:block">
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $workspaceName($workspace) }}</span>
                            <span class="block truncate text-[11px] text-slate-400">{{ $workspace->slug }}</span>
                        </span>
                        @if ($isCurrent)
                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                            </svg>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>

        <div class="mt-2 border-t border-slate-100 pt-2 dark:border-slate-800">
            <a href="{{ route('onboarding.business.create') }}"
               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">+</span>
                {{ __('app.create_workspace') }}
            </a>
            @can('business.view')
                <a href="{{ route('business.profile.edit', ['tenant' => $tenant->slug]) }}"
                   class="mt-1 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">⚙</span>
                    {{ __('app.workspace_settings') }}
                </a>
            @endcan
        </div>
    </div>
</div>