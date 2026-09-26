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
