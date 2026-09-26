@extends('layouts.guest')

@section('title', __('app.create_account').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.get_started') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ __('app.create_account') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.register_message') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                   placeholder="{{ __('app.name_placeholder') }}">
            @error('name')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                   placeholder="{{ __('app.email_placeholder') }}">
            @error('email')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @error('password')
                <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
            {{ __('app.create_account') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('app.have_account') }}
        <a href="{{ route('login') }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.login') }}</a>
    </p>
@endsection
