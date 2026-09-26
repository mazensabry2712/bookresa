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
