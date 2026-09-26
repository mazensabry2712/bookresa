@extends('layouts.guest')

@section('title', __('app.login').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.welcome_back') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ __('app.login') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.login_message') }}</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
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
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm transition focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('password')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-indigo focus:ring-brand-indigo dark:border-slate-600 dark:bg-slate-900">
            <span>{{ __('app.remember_me') }}</span>
        </label>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md focus-visible:outline-brand-indigo dark:bg-brand-indigo">
            {{ __('app.login') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.no_account') }}
        <a href="{{ route('register') }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.create_account') }}</a>
    </p>
@endsection
