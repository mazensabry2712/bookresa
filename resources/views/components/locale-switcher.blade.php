@props(['compact' => false])

@if ($compact)
    <div class="flex items-center gap-1 text-xs font-bold" role="group" aria-label="{{ __('app.language') }}">
        @foreach (config('bookresa.locales', ['en', 'ar']) as $index => $locale)
            @if ($index > 0)
                <span class="text-slate-300 dark:text-slate-700" aria-hidden="true">/</span>
            @endif
            @php
                $label = $locale === 'ar' ? __('app.arabic') : __('app.english');
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
               class="rounded-md px-1.5 py-1 transition {{ app()->getLocale() === $locale ? 'text-brand-navy dark:text-white' : 'text-slate-400 hover:text-brand-indigo dark:text-slate-500 dark:hover:text-indigo-300' }}"
               @if (app()->getLocale() === $locale) aria-current="page" @endif>
                {{ strtoupper($locale) }}
                <span class="sr-only">— {{ $label }}</span>
            </a>
        @endforeach
    </div>
@else
    <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="{{ __('app.language') }}">
        @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
            @php
                $label = $locale === 'ar' ? __('app.arabic') : __('app.english');
            @endphp
            <a href="{{ request()->fullUrlWithQuery(['locale' => $locale]) }}"
               class="rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ app()->getLocale() === $locale ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100' : 'text-slate-500 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800' }}"
               @if (app()->getLocale() === $locale) aria-current="page" @endif>
                {{ strtoupper($locale) }}
                <span class="sr-only">— {{ $label }}</span>
            </a>
        @endforeach
    </div>
@endif