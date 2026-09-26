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

        <form method="POST" action="{{ route('business.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
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
                <h3 class="font-semibold">{{ __('Branding') }}</h3>
                <div class="mt-4 grid gap-5 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium">{{ __('Logo') }}</p>
                        @if ($profile->logo_path)
                            <img src="{{ Storage::disk('public')->url($profile->logo_path) }}" alt="{{ __('Business logo') }}" class="mt-3 h-24 w-24 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                            <label class="mt-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                                {{ __('Remove current logo') }}
                            </label>
                        @endif
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm dark:border-slate-700">
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ __('Cover image') }}</p>
                        @if ($profile->cover_path)
                            <img src="{{ Storage::disk('public')->url($profile->cover_path) }}" alt="{{ __('Business cover') }}" class="mt-3 h-28 w-full rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-slate-700">
                            <label class="mt-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_cover" value="1" class="rounded border-slate-300">
                                {{ __('Remove current cover') }}
                            </label>
                        @endif
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm dark:border-slate-700">
                    </div>
                </div>
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <h3 class="font-semibold">{{ __('Booking payments') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Choose whether customers pay the full service price, a deposit, or later.') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @php $paymentMode = data_get($profile->booking_settings, 'payment_mode', 'pay_later'); @endphp
                    <label class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <input type="radio" name="payment_mode" value="full" @checked($paymentMode === 'full')>
                        <span class="ms-2 text-sm font-semibold">{{ __('Full payment') }}</span>
                    </label>
                    <label class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <input type="radio" name="payment_mode" value="deposit" @checked($paymentMode === 'deposit')>
                        <span class="ms-2 text-sm font-semibold">{{ __('Deposit') }}</span>
                    </label>
                    <label class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <input type="radio" name="payment_mode" value="pay_later" @checked($paymentMode === 'pay_later')>
                        <span class="ms-2 text-sm font-semibold">{{ __('Pay later') }}</span>
                    </label>
                </div>
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="checkbox" name="customer_email_required" value="1" @checked(data_get($profile->booking_settings, 'customer_email_required', false)) class="rounded border-slate-300">
                    {{ __('Require customer email during booking') }}
                </label>
                <label class="mt-4 block max-w-xs text-sm">
                    <span class="font-medium">{{ __('Customer limit policy') }}</span>
                    <select name="customer_limit_policy" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @php $customerLimitPolicy = data_get($profile->booking_settings, 'customer_limit_policy', 'allow_overage'); @endphp
                        <option value="allow_overage" @selected($customerLimitPolicy === 'allow_overage')>{{ __('Allow over-limit customers and charge usage fees') }}</option>
                        <option value="block_new_customers" @selected($customerLimitPolicy === 'block_new_customers')>{{ __('Block new customer profiles at the included limit') }}</option>
                    </select>
                </label>

                <label class="mt-4 block max-w-xs text-sm">
                    <span class="font-medium">{{ __('Deposit percentage') }}</span>
                    <input type="number" min="1" max="99" name="deposit_percent" value="{{ old('deposit_percent', data_get($profile->booking_settings, 'deposit_percent', 50)) }}" class="mt-1.5 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
            </section>

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
