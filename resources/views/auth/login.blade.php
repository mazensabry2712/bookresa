@extends('layouts.guest')

@section('title', __('app.login').' — BookResa')

@section('content')
    <div>
        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-indigo">{{ __('app.auth_access_eyebrow') }}</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-[-0.03em] text-slate-950 dark:text-white">{{ __('app.welcome_back') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.login_message') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-bookresa-login-form>
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="mt-2 block min-h-13 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                   placeholder="{{ __('app.email_placeholder') }}">
            @error('email')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <label for="password" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.password') }}</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-indigo hover:underline">{{ __('app.forgot_password') }}</a>
            </div>

            <div class="relative mt-2">
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="block min-h-13 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3.5 pe-12 text-sm text-slate-900 shadow-sm transition focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">

                <button type="button"
                        class="absolute inset-y-0 end-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200"
                        data-bookresa-password-toggle
                        aria-label="{{ __('app.show_password') }}"
                        aria-pressed="false">
                    <svg data-password-icon="show" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-5.25 9.5-5.25S21.5 12 21.5 12 18 17.25 12 17.25 2.5 12 2.5 12Z"/>
                        <circle cx="12" cy="12" r="2.5"/>
                    </svg>
                    <svg data-password-icon="hide" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.6 6.9A9.4 9.4 0 0 1 12 6.75c6 0 9.5 5.25 9.5 5.25a16.5 16.5 0 0 1-3.1 3.35M7.2 7.3C4.2 9.2 2.5 12 2.5 12S6 17.25 12 17.25c1.15 0 2.2-.18 3.15-.48"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.85 9.85a3 3 0 0 0 4.3 4.3"/>
                    </svg>
                </button>
            </div>

            @error('password')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex min-h-8 cursor-pointer items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-indigo focus:ring-brand-indigo dark:border-slate-600 dark:bg-slate-900">
            <span>{{ __('app.remember_me') }}</span>
        </label>

        <button type="submit"
                class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:bg-slate-800 hover:shadow-md focus-visible:outline-brand-indigo disabled:cursor-not-allowed disabled:opacity-70 dark:bg-brand-indigo dark:hover:bg-indigo-400"
                data-bookresa-login-submit>
            <span data-bookresa-login-label>{{ __('app.login') }}</span>
            <span class="hidden items-center gap-2" data-bookresa-login-loading aria-live="polite">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="2"/>
                    <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>{{ __('app.logging_in') }}</span>
            </span>
        </button>
    </form>

    <div class="mt-7 border-t border-slate-100 pt-6 text-center dark:border-slate-800">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ __('app.no_account') }}
            <a href="{{ route('register') }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.create_account') }}</a>
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.querySelector('#password');
            const passwordToggle = document.querySelector('[data-bookresa-password-toggle]');
            const showIcon = document.querySelector('[data-password-icon="show"]');
            const hideIcon = document.querySelector('[data-password-icon="hide"]');
            const form = document.querySelector('[data-bookresa-login-form]');
            const submit = document.querySelector('[data-bookresa-login-submit]');
            const label = document.querySelector('[data-bookresa-login-label]');
            const loading = document.querySelector('[data-bookresa-login-loading]');

            if (passwordInput && passwordToggle && showIcon && hideIcon) {
                passwordToggle.addEventListener('click', () => {
                    const isVisible = passwordInput.type === 'text';

                    passwordInput.type = isVisible ? 'password' : 'text';
                    showIcon.classList.toggle('hidden', !isVisible);
                    hideIcon.classList.toggle('hidden', isVisible);
                    passwordToggle.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                    passwordToggle.setAttribute(
                        'aria-label',
                        isVisible
                            ? @json(__('app.show_password'))
                            : @json(__('app.hide_password'))
                    );
                });
            }

            if (form && submit && label && loading) {
                form.addEventListener('submit', () => {
                    submit.disabled = true;
                    submit.setAttribute('aria-busy', 'true');
                    label.classList.add('hidden');
                    loading.classList.remove('hidden');
                    loading.classList.add('inline-flex');
                });
            }
        });
    </script>
@endsection