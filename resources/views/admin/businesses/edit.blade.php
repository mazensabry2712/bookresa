@extends('layouts.admin')

@section('title', __('platform.edit_workspace').' — BookResa')
@section('heading', __('platform.edit_workspace'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">{{ __('platform.workspace_control') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.edit_workspace') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $tenant->slug }}</p>
        </div>
        <a href="{{ route('admin.businesses.show', $tenant) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('platform.back_to_workspace') }}</a>
    </div>

    <form method="POST" action="{{ route('admin.businesses.update', $tenant) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_name_en') }}</label>
                    <input name="business_name_en" value="{{ old('business_name_en', data_get($tenant->profile?->name, 'en')) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_name_ar') }}</label>
                    <input name="business_name_ar" value="{{ old('business_name_ar', data_get($tenant->profile?->name, 'ar')) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_type') }}</label>
                    <select name="business_type_id" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach($businessTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('business_type_id', $tenant->business_type_id) == $type->id)>
                                {{ data_get($type->name, app()->getLocale()) ?? data_get($type->name, 'en') ?? $type->slug }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.slug') }}</label>
                    <input name="slug" value="{{ old('slug', $tenant->slug) }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_phone') }}</label>
                    <input name="phone" value="{{ old('phone', $tenant->profile?->phone) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $tenant->profile?->email) }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.timezone') }}</label>
                    <input name="timezone" value="{{ old('timezone', $tenant->profile?->timezone ?? 'Africa/Cairo') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.locale') }}</label>
                    <select name="locale" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach(config('bookresa.locales', ['en','ar']) as $locale)
                            <option value="{{ $locale }}" @selected(old('locale', $tenant->profile?->locale ?? 'en') === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.status') }}</label>
                    <select name="status" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach(['active','suspended','archived'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $tenant->status->value) === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('admin.businesses.show', $tenant) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-slate-700">{{ __('platform.cancel') }}</a>
            <button class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('platform.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
