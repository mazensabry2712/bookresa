@extends('layouts.guest')

@section('title', __('app.two_factor').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.account_security') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.two_factor') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.two_factor_message') }}</p>
    </div>

    <form method="POST" action="{{ route('two-factor.login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.authentication_code') }}</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-center text-lg tracking-[0.35em] text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('code')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="recovery_code" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.recovery_code') }}</label>
            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 font-mono text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.recovery_code_help') }}</p>
            @error('recovery_code')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm dark:bg-brand-indigo">
            {{ __('app.continue') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('login') }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.back_to_login') }}</a>
    </p>
@endsection
