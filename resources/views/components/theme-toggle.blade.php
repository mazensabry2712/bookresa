<div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="{{ __('app.theme') }}">
    <button type="button" data-bookresa-theme="light" class="rounded-lg px-2.5 py-1.5 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="{{ __('app.light') }}">
        {{ __('app.light') }}
    </button>
    <button type="button" data-bookresa-theme="dark" class="rounded-lg px-2.5 py-1.5 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="{{ __('app.dark') }}">
        {{ __('app.dark') }}
    </button>
    <button type="button" data-bookresa-theme="system" class="rounded-lg px-2.5 py-1.5 text-xs font-medium hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="{{ __('app.system') }}">
        {{ __('app.system') }}
    </button>
</div>
<script>
    document.querySelectorAll('[data-bookresa-theme]').forEach((button) => {
        button.addEventListener('click', () => window.BookResaTheme?.set(button.dataset.bookresaTheme));
    });
</script>
