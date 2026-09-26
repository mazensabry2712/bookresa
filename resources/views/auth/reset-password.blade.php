@extends('layouts.guest')

@section('title', __('app.reset_password').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.account_access') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.reset_password') }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.reset_password_message') }}</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="email"
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
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
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        </div>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm dark:bg-brand-indigo">
            {{ __('app.reset_password') }}
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
        <a href="{{ route('login') }}" class="font-bold text-brand-indigo hover:underline">{{ __('app.back_to_login') }}</a>
    </p>
@endsection
