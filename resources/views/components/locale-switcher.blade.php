@props(['compact' => false])

@if ($compact)
    <div class="flex items-center gap-1 rounded-xl border border-slate-200/80 bg-slate-100/80 p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800/70"
         role="group"
         aria-label="{{ __('app.language') }}">
        @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
            @php
                $label = $locale === 'ar' ? __('app.arabic') : __('app.english');
                $active = app()->getLocale() === $locale;
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
               class="inline-flex min-w-9 items-center justify-center rounded-lg px-2.5 py-1.5 text-[11px] font-extrabold tracking-wide transition {{ $active ? 'bg-white text-brand-navy shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:bg-white/80 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700/70 dark:hover:text-slate-100' }}"
               @if ($active) aria-current="page" @endif
               title="{{ $label }}">
                {{ strtoupper($locale) }}
                <span class="sr-only">— {{ $label }}</span>
            </a>
        @endforeach
    </div>
@else
    <div class="inline-flex items-center gap-0.5 rounded-2xl border border-slate-200/90 bg-slate-100/90 p-1 shadow-sm ring-1 ring-black/[0.02] dark:border-slate-700 dark:bg-slate-800/80 dark:ring-white/[0.03]"
         role="group"
         aria-label="{{ __('app.language') }}">
        <span class="hidden px-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 sm:inline">{{ __('app.language') }}</span>

        @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
            @php
                $label = $locale === 'ar' ? __('app.arabic') : __('app.english');
                $active = app()->getLocale() === $locale;
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
               class="inline-flex min-h-8 min-w-10 items-center justify-center rounded-xl px-2.5 text-[11px] font-extrabold tracking-wide transition-all {{ $active ? 'bg-white text-brand-navy shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:bg-white/80 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700/70 dark:hover:text-slate-100' }}"
               @if ($active) aria-current="page" @endif
               title="{{ $label }}">
                {{ strtoupper($locale) }}
                <span class="sr-only">— {{ $label }}</span>
            </a>
        @endforeach
    </div>
@endif