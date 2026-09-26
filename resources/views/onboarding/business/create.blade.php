@extends('layouts.guest')

@section('title', __('app.create_business').' — BookResa')

@section('content')
    <div>
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.step_progress', ['current' => 1, 'total' => 6]) }}</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">{{ __('app.create_business') }}</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.create_business_message') }}</p>
            </div>
        </div>

        <div class="mt-6 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" aria-hidden="true">
            <div class="h-full w-1/6 rounded-full bg-brand-indigo"></div>
        </div>
    </div>

    <form method="POST" action="{{ route('onboarding.business.store') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.business_name') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autofocus
                   class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                   placeholder="{{ __('app.business_name_placeholder') }}">
            @error('name') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="business_type_id" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.business_type') }}</label>
            <select id="business_type_id" name="business_type_id" required
                    class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <option value="">{{ __('app.select_business_type') }}</option>
                @foreach ($businessTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('business_type_id') == $type->id)>
                        {{ data_get($type->name, app()->getLocale()) ?? data_get($type->name, 'en') ?? $type->slug }}
                    </option>
                @endforeach
            </select>
            @error('business_type_id') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="slug" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.booking_slug') }}</label>
            <div class="mt-2 flex min-w-0 items-stretch">
                <span class="hidden shrink-0 items-center rounded-s-xl border border-e-0 border-slate-300 bg-slate-50 px-3 text-xs font-medium text-slate-500 sm:flex dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">/</span>
                <input id="slug" name="slug" value="{{ old('slug') }}" maxlength="120"
                       class="block min-w-0 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white sm:rounded-s-xl"
                       placeholder="{{ __('app.booking_slug_placeholder') }}">
            </div>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ __('app.booking_slug_help') }}</p>
            @error('slug') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="timezone" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.timezone') }}</label>
                <input id="timezone" name="timezone" value="{{ old('timezone', config('app.timezone', 'UTC')) }}" required list="bookresa-timezones"
                       class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <datalist id="bookresa-timezones">
                    @foreach (['Africa/Cairo', 'Asia/Riyadh', 'Asia/Dubai', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'UTC'] as $timezone)
                        <option value="{{ $timezone }}"></option>
                    @endforeach
                </datalist>
                @error('timezone') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="locale" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.workspace_language') }}</label>
                <select id="locale" name="locale"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-indigo focus:ring-2 focus:ring-brand-indigo/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <option value="en" @selected(old('locale', app()->getLocale()) === 'en')>{{ __('app.english') }}</option>
                    <option value="ar" @selected(old('locale', app()->getLocale()) === 'ar')>{{ __('app.arabic') }}</option>
                </select>
                @error('locale') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <details class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60">
            <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.optional_business_details') }}</summary>
            <div class="mt-4 space-y-5">
                <div>
                    <label for="phone" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.phone') }}</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="40" autocomplete="tel"
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('phone') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800 dark:text-slate-200">{{ __('app.business_email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"
                           class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950"
                           placeholder="{{ __('app.email_placeholder') }}">
                    @error('email') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </details>

        <button type="submit"
                class="w-full rounded-xl bg-brand-navy px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:-translate-y-px hover:shadow-md dark:bg-brand-indigo">
            {{ __('app.continue_to_workspace') }}
        </button>
    </form>
@endsection
