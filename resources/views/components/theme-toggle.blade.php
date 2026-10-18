@props(['compact' => false])

@php
    $themeMenuId = 'bookresa-theme-menu-'.\Illuminate\Support\Str::uuid();
@endphp

<div class="relative" data-bookresa-theme-switcher>
    <button type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
            data-bookresa-theme-toggle
            aria-expanded="false"
            aria-controls="{{ $themeMenuId }}"
            aria-haspopup="true"
            aria-label="{{ __('app.theme') }}"
            title="{{ __('app.theme') }}">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 3a9 9 0 0 0 0 18V3Z" fill="currentColor" stroke="none"/>
        </svg>
    </button>

    <div id="{{ $themeMenuId }}"
         class="hidden absolute end-0 top-[calc(100%+0.5rem)] z-[90] w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
         data-bookresa-theme-panel
         role="group"
         aria-label="{{ __('app.theme') }}"
         aria-hidden="true">
        <p class="px-3 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.theme') }}</p>

        @foreach (['light', 'dark', 'system'] as $theme)
            <button type="button"
                    data-bookresa-theme="{{ $theme }}"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800"
                    aria-label="{{ __('app.'.$theme) }}"
                    aria-pressed="false"
                    title="{{ __('app.'.$theme) }}">
                @if ($theme === 'light')
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                    </svg>
                @elseif ($theme === 'dark')
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.1A8.2 8.2 0 0 1 8.9 3.8 8.4 8.4 0 1 0 20.2 15.1Z"/>
                    </svg>
                @else
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <rect x="3.5" y="4.5" width="17" height="15" rx="2"/>
                        <path stroke-linecap="round" d="M3.5 9h17M12 9v10.5"/>
                    </svg>
                @endif
                <span>{{ __('app.'.$theme) }}</span>
                <svg class="ms-auto hidden h-4 w-4 shrink-0" data-bookresa-theme-selected viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/>
                </svg>
            </button>
        @endforeach
    </div>
</div>
