@props(['compact' => false])

@if ($compact)
    <div class="inline-flex items-center gap-0.5 rounded-xl border border-slate-200/80 bg-slate-100/80 p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800/70"
         role="group"
         aria-label="{{ __('app.theme') }}">
        @foreach (['light', 'dark', 'system'] as $theme)
            <button
                type="button"
                data-bookresa-theme="{{ $theme }}"
                class="group relative inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-all hover:bg-white hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                aria-label="{{ __('app.'.$theme) }}"
                aria-pressed="false"
                title="{{ __('app.'.$theme) }}"
            >
                @if ($theme === 'light')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                    </svg>
                @elseif ($theme === 'dark')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.1A8.2 8.2 0 0 1 8.9 3.8 8.4 8.4 0 1 0 20.2 15.1Z"/>
                    </svg>
                @else
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <rect x="3.5" y="4.5" width="17" height="15" rx="2"/>
                        <path stroke-linecap="round" d="M3.5 9h17M12 9v10.5"/>
                    </svg>
                @endif
                <span class="sr-only">{{ __('app.'.$theme) }}</span>
            </button>
        @endforeach
    </div>
@else
    <div class="inline-flex items-center gap-0.5 rounded-2xl border border-slate-200/90 bg-slate-100/90 p-1 shadow-sm ring-1 ring-black/[0.02] dark:border-slate-700 dark:bg-slate-800/80 dark:ring-white/[0.03]"
         role="group"
         aria-label="{{ __('app.theme') }}">
        <span class="hidden px-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 sm:inline">{{ __('app.theme') }}</span>

        @foreach (['light', 'dark', 'system'] as $theme)
            <button
                type="button"
                data-bookresa-theme="{{ $theme }}"
                class="group relative inline-flex min-h-8 min-w-9 items-center justify-center rounded-xl px-2 text-slate-400 transition-all hover:bg-white hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                aria-label="{{ __('app.'.$theme) }}"
                aria-pressed="false"
                title="{{ __('app.'.$theme) }}"
            >
                @if ($theme === 'light')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"/>
                        <path stroke-linecap="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                    </svg>
                @elseif ($theme === 'dark')
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.2 15.1A8.2 8.2 0 0 1 8.9 3.8 8.4 8.4 0 1 0 20.2 15.1Z"/>
                    </svg>
                @else
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <rect x="3.5" y="4.5" width="17" height="15" rx="2"/>
                        <path stroke-linecap="round" d="M3.5 9h17M12 9v10.5"/>
                    </svg>
                @endif
                <span class="sr-only">{{ __('app.'.$theme) }}</span>
            </button>
        @endforeach
    </div>
@endif