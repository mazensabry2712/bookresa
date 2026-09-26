@props(['compact' => false])

@if ($compact)
    <div class="flex items-center gap-0.5" role="group" aria-label="{{ __('app.theme') }}">
        @foreach (['light', 'dark', 'system'] as $theme)
            <button
                type="button"
                data-bookresa-theme="{{ $theme }}"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-900 dark:hover:text-slate-200"
                aria-label="{{ __('app.'.$theme) }}"
                aria-pressed="false"
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
    <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="{{ __('app.theme') }}">
        @foreach (['light', 'dark', 'system'] as $theme)
            <button
                type="button"
                data-bookresa-theme="{{ $theme }}"
                class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="{{ __('app.'.$theme) }}"
                aria-pressed="false"
            >
                {{ __('app.'.$theme) }}
            </button>
        @endforeach
    </div>
@endif