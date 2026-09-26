@extends('layouts.admin')

@section('title', __('Platform settings').' — BookResa')
@section('heading', __('Platform settings'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform operations') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Platform settings') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage global defaults and operational contact details for BookResa.') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Platform name') }}</span>
                    <input name="platform_name" value="{{ old('platform_name', $values['platform_name']) }}" required maxlength="120"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Support email') }}</span>
                    <input type="email" name="support_email" value="{{ old('support_email', $values['support_email']) }}" maxlength="255"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Default locale') }}</span>
                    <select name="default_locale" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($locales as $locale)
                            <option value="{{ $locale }}" @selected(old('default_locale', $values['default_locale']) === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Default timezone') }}</span>
                    <input name="default_timezone" value="{{ old('default_timezone', $values['default_timezone']) }}" required
                        placeholder="Africa/Cairo"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>

                <label class="text-sm">
                    <span class="mb-1 block font-medium">{{ __('Booking slot interval (minutes)') }}</span>
                    <input type="number" name="booking_slot_interval_minutes" min="5" max="120" step="5"
                        value="{{ old('booking_slot_interval_minutes', $values['booking_slot_interval_minutes']) }}" required
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
            </div>

            <div class="flex justify-end">
                <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save settings') }}</button>
            </div>
        </form>
    </div>
@endsection
