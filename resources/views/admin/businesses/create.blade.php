@extends('layouts.admin')

@section('title', __('platform.create_workspace').' — BookResa')
@section('heading', __('platform.create_workspace'))

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">{{ __('platform.workspace_control') }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('platform.create_workspace') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('platform.create_workspace_help') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.businesses.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h3 class="font-semibold">{{ __('platform.owner_account') }}</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.owner_name') }}</label>
                    <input name="owner_name" value="{{ old('owner_name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('owner_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.owner_email') }}</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('owner_email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.owner_password') }}</label>
                    <input type="password" name="owner_password" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('owner_password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.owner_password_confirmation') }}</label>
                    <input type="password" name="owner_password_confirmation" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h3 class="font-semibold">{{ __('platform.workspace_details') }}</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_name_en') }}</label>
                    <input name="business_name_en" value="{{ old('business_name_en') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_name_ar') }}</label>
                    <input name="business_name_ar" value="{{ old('business_name_ar') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_type') }}</label>
                    <select name="business_type_id" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach($businessTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('business_type_id') == $type->id)>
                                {{ data_get($type->name, app()->getLocale()) ?? data_get($type->name, 'en') ?? $type->slug }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.slug') }}</label>
                    <input name="slug" value="{{ old('slug') }}" placeholder="{{ __('platform.slug_auto') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_phone') }}</label>
                    <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.business_email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.timezone') }}</label>
                    <input name="timezone" value="{{ old('timezone', 'Africa/Cairo') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.locale') }}</label>
                    <select name="locale" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach(config('bookresa.locales', ['en','ar']) as $locale)
                            <option value="{{ $locale }}" @selected(old('locale', 'en') === $locale)>{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold">{{ __('platform.status') }}</label>
                    <select name="status" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach(['active','suspended','archived'] as $status)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('admin.businesses.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold dark:border-slate-700">{{ __('platform.cancel') }}</a>
            <button class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('platform.create_workspace') }}</button>
        </div>
    </form>
</div>
@endsection
