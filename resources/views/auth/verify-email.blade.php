@extends('layouts.guest')

@section('title', __('app.verify_email_title').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.account_security') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.verify_email_title') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.verify_email_message') }}</p>
    </div>

    @if (in_array(session('status'), ['verification-link-sent', 'verification-code-sent'], true))
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ __('app.verification_code_sent') }}
        </div>
    @endif

    <form class="mt-7 space-y-4" method="POST" action="{{ route('verification.otp.verify') }}">
        @csrf

        <div>
            <label for="verification-code" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                {{ __('app.verify_email_code_label') }}
            </label>
            <input id="verification-code"
                   name="code"
                   type="text"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   pattern="[0-9]{6}"
                   minlength="6"
                   maxlength="6"
                   required
                   autofocus
                   dir="ltr"
                   value="{{ old('code') }}"
                   placeholder="{{ __('app.verify_email_code_placeholder') }}"
                   aria-describedby="verification-code-help"
                   class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-2xl font-extrabold tracking-[0.45em] text-slate-950 shadow-sm outline-none transition placeholder:text-slate-300 focus:border-brand-indigo focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-600">
            <p id="verification-code-help" class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                {{ __('app.verify_email_code_expiry', ['minutes' => 10]) }}
            </p>
            @error('code')
                <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
            {{ __('app.verify_email_submit_code') }}
        </button>
    </form>

    <div class="mt-4 space-y-3">
        <form method="POST" action="{{ route('verification.otp.resend') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                {{ __('app.resend_verification') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                {{ __('app.logout') }}
            </button>
        </form>
    </div>
@endsection
