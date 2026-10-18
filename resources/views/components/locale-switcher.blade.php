@props(['compact' => false])

@php
    $localeMenuId = 'bookresa-language-menu-'.\Illuminate\Support\Str::uuid();
@endphp

<div class="relative" data-bookresa-language-switcher>
    <button type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
            data-bookresa-language-toggle
            aria-expanded="false"
            aria-controls="{{ $localeMenuId }}"
            aria-haspopup="true"
            aria-label="{{ __('app.language') }}"
            title="{{ __('app.language') }}">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <circle cx="12" cy="12" r="9"/>
            <ellipse cx="12" cy="12" rx="4" ry="9"/>
            <path stroke-linecap="round" d="M3 12h18M4.5 7h15M4.5 17h15"/>
        </svg>
    </button>

    <div id="{{ $localeMenuId }}"
         class="hidden absolute end-0 top-[calc(100%+0.5rem)] z-[90] w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/25"
         data-bookresa-language-panel
         role="group"
         aria-label="{{ __('app.language') }}"
         aria-hidden="true">
        <p class="px-3 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ __('app.language') }}</p>

        @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
            @php
                $label = $locale === 'ar' ? __('app.arabic') : __('app.english');
                $active = app()->getLocale() === $locale;
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
               data-bookresa-language-option
               class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-slate-100 text-brand-navy dark:bg-slate-800 dark:text-white' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800' }}"
               @if ($active) aria-current="page" @endif
               title="{{ $label }}">
                <span>{{ $label }}</span>
                <span class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-slate-400">{{ $locale }}</span>
            </a>
        @endforeach
    </div>
</div>
