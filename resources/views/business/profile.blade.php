@extends('layouts.dashboard')

@section('title', __('Business Profile').' — '.config('app.name', 'BookResa'))
@section('heading', __('Business Profile'))

@section('content')
    <div class="max-w-5xl space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Workspace settings') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Business Profile') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Keep the public business information and booking preferences up to date.') }}</p>
        </div>

        <form method="POST" action="{{ route('business.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h3 class="font-semibold">{{ __('Basic information') }}</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Name (English)') }}</span>
                        <input name="name_en" value="{{ old('name_en', data_get($profile->name, 'en')) }}" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Name (Arabic)') }}</span>
                        <input name="name_ar" value="{{ old('name_ar', data_get($profile->name, 'ar')) }}" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium">{{ __('Description (English)') }}</span>
                        <textarea name="description_en" rows="3" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('description_en', data_get($profile->description, 'en')) }}</textarea>
                    </label>
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium">{{ __('Description (Arabic)') }}</span>
                        <textarea name="description_ar" rows="3" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('description_ar', data_get($profile->description, 'ar')) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h3 class="font-semibold">{{ __('Contact & location') }}</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Phone') }}</span>
                        <input name="phone" value="{{ old('phone', $profile->phone) }}" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Email') }}</span>
                        <input type="email" name="email" value="{{ old('email', $profile->email) }}" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Location') }}</span>
                        <input name="location" value="{{ old('location', $profile->location) }}" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Timezone') }}</span>
                        <select name="timezone" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            @foreach (DateTimeZone::listIdentifiers() as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $profile->timezone) === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="space-y-1 text-sm sm:col-span-2">
                        <span class="font-medium">{{ __('Address') }}</span>
                        <textarea name="address" rows="3" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('address', $profile->address) }}</textarea>
                    </label>
                    <label class="space-y-1 text-sm">
                        <span class="font-medium">{{ __('Interface language') }}</span>
                        <select name="locale" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
                                <option value="{{ $locale }}" @selected(old('locale', $profile->locale) === $locale)>{{ strtoupper($locale) }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h3 class="font-semibold">{{ __('Social links') }}</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach (['website' => 'Website', 'facebook' => 'Facebook', 'instagram' => 'Instagram'] as $key => $label)
                        <label class="space-y-1 text-sm">
                            <span class="font-medium">{{ __($label) }}</span>
                            <input type="url" name="{{ $key }}" value="{{ old($key, data_get($profile->social_links, $key)) }}" placeholder="https://" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                    {{ __('Save changes') }}
                </button>
            </div>
        </form>
    </div>
@endsection
