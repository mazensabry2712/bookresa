@extends('layouts.guest')

@section('title', __('app.verify_email_title').' — BookResa')

@section('content')
    <div>
        <p class="text-sm font-semibold text-brand-indigo">{{ __('app.account_security') }}</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ __('app.verify_email_title') }}</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.verify_email_message') }}</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">
            {{ __('app.verification_link_sent') }}
        </div>
    @endif

    <div class="mt-7 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
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
