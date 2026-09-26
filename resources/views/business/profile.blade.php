@extends('layouts.dashboard')

@section('title', __('business_ui.business_profile').' — '.config('app.name', 'BookResa'))
@section('heading', __('business_ui.business_profile'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $paymentMode = data_get($profile->booking_settings, 'payment_mode', 'pay_later');
        $customerLimitPolicy = data_get($profile->booking_settings, 'customer_limit_policy', 'allow_overage');
        $paymentsEnabled = $tenant->modules->contains('key', 'payments')
            && $tenant->modules->firstWhere('key', 'payments')?->pivot?->enabled;

        $bookingUrl = route('public.booking.canonical.show', ['tenant' => $tenant->slug]);
    @endphp

    <div class="max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="relative h-32 bg-brand-navy sm:h-40">
                @if ($profile->cover_path)
                    <img src="{{ Storage::disk('public')->url($profile->cover_path) }}"
                         alt="{{ __('business_ui.cover_image') }}"
                         class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-brand-navy/40"></div>
                @endif
            </div>

            <div class="relative px-5 pb-5 sm:px-6 sm:pb-6">
                <div class="-mt-10 flex flex-col gap-4 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex min-w-0 items-end gap-4">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-brand-soft text-2xl font-extrabold text-brand-navy shadow-sm dark:border-slate-900 dark:bg-slate-800 dark:text-indigo-300 sm:h-24 sm:w-24">
                            @if ($profile->logo_path)
                                <img src="{{ Storage::disk('public')->url($profile->logo_path) }}"
                                     alt="{{ __('business_ui.logo') }}"
                                     class="h-full w-full object-cover">
                            @else
                                {{ str($localized($profile->name))->substr(0, 1)->upper() }}
                            @endif
                        </div>

                        <div class="min-w-0 pb-1">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('business_ui.business_profile') }}</p>
                            <h2 class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-950 dark:text-white">
                                {{ $localized($profile->name) }}
                            </h2>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $tenant->slug }}</p>
                        </div>
                    </div>

                    <a href="{{ $bookingUrl }}"
                       target="_blank"
                       rel="noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-600">
                        {{ __('business_ui.open_booking_page') }}
                    </a>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('business.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.basic_information') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.basic_information_help') }}</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.name_english') }}</span>
                        <input name="name_en" value="{{ old('name_en', data_get($profile->name, 'en')) }}" required maxlength="160"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.name_arabic') }}</span>
                        <input name="name_ar" value="{{ old('name_ar', data_get($profile->name, 'ar')) }}" maxlength="160" dir="rtl"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label class="space-y-1.5 text-sm sm:col-span-2">
                        <span class="font-semibold">{{ __('business_ui.description_english') }}</span>
                        <textarea name="description_en" rows="3" maxlength="5000"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('description_en', data_get($profile->description, 'en')) }}</textarea>
                    </label>

                    <label class="space-y-1.5 text-sm sm:col-span-2">
                        <span class="font-semibold">{{ __('business_ui.description_arabic') }}</span>
                        <textarea name="description_ar" rows="3" maxlength="5000" dir="rtl"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('description_ar', data_get($profile->description, 'ar')) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.branding') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.branding_help') }}</p>
                </div>

                <div class="mt-5 grid gap-6 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-semibold">{{ __('business_ui.logo') }}</p>
                        <div class="mt-3 flex items-center gap-4">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-brand-soft text-xl font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                                @if ($profile->logo_path)
                                    <img src="{{ Storage::disk('public')->url($profile->logo_path) }}" alt="{{ __('business_ui.logo') }}" class="h-full w-full object-cover">
                                @else
                                    {{ str($localized($profile->name))->substr(0, 1)->upper() }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"
                                       class="block w-full rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm dark:border-slate-700">
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('business_ui.logo_help') }}</p>
                            </div>
                        </div>
                        @if ($profile->logo_path)
                            <label class="mt-4 flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300">
                                {{ __('business_ui.remove_logo') }}
                            </label>
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold">{{ __('business_ui.cover_image') }}</p>
                        <div class="mt-3 overflow-hidden rounded-2xl bg-brand-soft dark:bg-slate-800">
                            @if ($profile->cover_path)
                                <img src="{{ Storage::disk('public')->url($profile->cover_path) }}" alt="{{ __('business_ui.cover_image') }}" class="h-28 w-full object-cover">
                            @else
                                <div class="flex h-28 items-center justify-center text-sm font-medium text-slate-500">{{ __('business_ui.no_cover') }}</div>
                            @endif
                        </div>
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"
                               class="mt-3 block w-full rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm dark:border-slate-700">
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('business_ui.cover_help') }}</p>
                        @if ($profile->cover_path)
                            <label class="mt-3 flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_cover" value="1" class="rounded border-slate-300">
                                {{ __('business_ui.remove_cover') }}
                            </label>
                        @endif
                    </div>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.contact_location') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.contact_location_help') }}</p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.phone') }}</span>
                        <input name="phone" value="{{ old('phone', $profile->phone) }}" maxlength="40" inputmode="tel"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.email') }}</span>
                        <input type="email" name="email" value="{{ old('email', $profile->email) }}" maxlength="255"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.location') }}</span>
                        <input name="location" value="{{ old('location', $profile->location) }}" maxlength="255"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                    </label>

                    <label class="space-y-1.5 text-sm">
                        <span class="font-semibold">{{ __('business_ui.timezone') }}</span>
                        <select name="timezone" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                            @foreach (DateTimeZone::listIdentifiers() as $timezone)
                                <option value="{{ $timezone }}" @selected(old('timezone', $profile->timezone) === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="space-y-1.5 text-sm sm:col-span-2">
                        <span class="font-semibold">{{ __('business_ui.address') }}</span>
                        <textarea name="address" rows="3" maxlength="2000"
                                  class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('address', $profile->address) }}</textarea>
                    </label>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.booking_settings') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.booking_settings_help') }}</p>
                </div>

                <div class="mt-5 space-y-6">
                    <div>
                        <p class="text-sm font-semibold">{{ __('business_ui.payment_mode') }}</p>
                        @if ($paymentsEnabled)
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                @foreach ([
                                    'full' => ['title' => __('business_ui.full_payment'), 'help' => __('business_ui.full_payment_help')],
                                    'deposit' => ['title' => __('business_ui.deposit'), 'help' => __('business_ui.deposit_help')],
                                    'pay_later' => ['title' => __('business_ui.pay_later'), 'help' => __('business_ui.pay_later_help')],
                                ] as $value => $option)
                                    <label class="cursor-pointer rounded-xl border p-4 transition hover:border-brand-indigo {{ $paymentMode === $value ? 'border-brand-indigo bg-indigo-50/70 dark:bg-indigo-950/30' : 'border-slate-200 dark:border-slate-800' }}">
                                        <span class="flex items-start gap-3">
                                            <input type="radio" name="payment_mode" value="{{ $value }}" @checked($paymentMode === $value) class="mt-0.5">
                                            <span>
                                                <span class="block text-sm font-bold">{{ $option['title'] }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $option['help'] }}</span>
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-4 max-w-xs">
                                <label class="space-y-1.5 text-sm">
                                    <span class="font-semibold">{{ __('business_ui.deposit_percent') }}</span>
                                    <input type="number" name="deposit_percent" min="1" max="99"
                                           value="{{ old('deposit_percent', data_get($profile->booking_settings, 'deposit_percent', 50)) }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                </label>
                            </div>
                        @else
                            <input type="hidden" name="payment_mode" value="pay_later">
                            <p class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300">
                                {{ __('business_ui.payments_module_disabled') }}
                            </p>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('business_ui.minimum_notice') }}</span>
                            <input type="number" name="minimum_notice_minutes" min="0" max="43200"
                                   value="{{ old('minimum_notice_minutes', data_get($profile->booking_settings, 'minimum_notice_minutes', 0)) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                            <span class="block text-xs leading-5 text-slate-500">{{ __('business_ui.minimum_notice_help') }}</span>
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('business_ui.maximum_advance') }}</span>
                            <input type="number" name="maximum_advance_days" min="1" max="730"
                                   value="{{ old('maximum_advance_days', data_get($profile->booking_settings, 'maximum_advance_days')) }}"
                                   placeholder="{{ __('business_ui.no_limit') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                            <span class="block text-xs leading-5 text-slate-500">{{ __('business_ui.maximum_advance_help') }}</span>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex items-start gap-3 rounded-xl br-surface-soft p-4 text-sm">
                            <input type="checkbox" name="customer_email_required" value="1"
                                   @checked(data_get($profile->booking_settings, 'customer_email_required', false))
                                   class="mt-0.5 rounded border-slate-300">
                            <span>
                                <span class="block font-semibold">{{ __('business_ui.require_email') }}</span>
                                <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('business_ui.require_email_help') }}</span>
                            </span>
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('business_ui.customer_limit_policy') }}</span>
                            <select name="customer_limit_policy" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                <option value="allow_overage" @selected($customerLimitPolicy === 'allow_overage')>{{ __('business_ui.allow_overage') }}</option>
                                <option value="block_new_customers" @selected($customerLimitPolicy === 'block_new_customers')>{{ __('business_ui.block_new_customers') }}</option>
                            </select>
                        </label>
                    </div>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.language_appearance') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.language_appearance_help') }}</p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl br-surface-soft p-4">
                        <p class="text-sm font-semibold">{{ __('business_ui.interface_language') }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('business_ui.interface_language_help') }}</p>
                        <div class="mt-4 w-fit">
                            <x-locale-switcher />
                        </div>
                        <select name="locale" class="mt-4 w-full max-w-xs rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach (config('bookresa.locales', ['en', 'ar']) as $locale)
                                <option value="{{ $locale }}" @selected(old('locale', $profile->locale ?? app()->getLocale()) === $locale)">
                                    {{ $locale === 'ar' ? __('app.arabic') : __('app.english') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-xl br-surface-soft p-4">
                        <p class="text-sm font-semibold">{{ __('business_ui.theme') }}</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('business_ui.theme_help') }}</p>
                        <div class="mt-4 w-fit">
                            <x-theme-toggle />
                        </div>
                    </div>
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.public_presence') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.public_presence_help') }}</p>
                </div>

                <div class="mt-5 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-600 dark:text-indigo-300">{{ __('business_ui.booking_url') }}</p>
                    <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                        <input value="{{ $bookingUrl }}" readonly class="min-w-0 flex-1 rounded-xl border border-indigo-200 bg-white px-3.5 py-3 text-sm text-slate-700 dark:border-indigo-900 dark:bg-slate-950 dark:text-slate-200" data-bookresa-booking-url>
                        <button type="button" data-bookresa-copy-booking-url
                                class="min-h-11 rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-bold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-900 dark:bg-slate-950 dark:text-indigo-300 dark:hover:bg-indigo-950/40">
                            {{ __('business_ui.copy_url') }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-indigo-700 dark:text-indigo-300" data-bookresa-copy-feedback aria-live="polite"></p>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        'website' => __('business_ui.website'),
                        'facebook' => __('business_ui.facebook'),
                        'instagram' => __('business_ui.instagram'),
                    ] as $key => $label)
                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ $label }}</span>
                            <input type="url" name="{{ $key }}" value="{{ old($key, data_get($profile->social_links, $key)) }}"
                                   placeholder="https://" maxlength="500"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="br-panel p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('business_ui.team_roles') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('business_ui.team_roles_help') }}</p>
                    </div>
                    @can('staff.view')
                        <a href="{{ route('staff.index') }}"
                           class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            {{ __('business_ui.manage_team') }}
                        </a>
                    @endcan
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs leading-5 text-slate-500">{{ __('business_ui.save_help') }}</p>
                @can('business.update')
                    <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                        {{ __('business_ui.save_changes') }}
                    </button>
                @endcan
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const button = document.querySelector('[data-bookresa-copy-booking-url]');
            const input = document.querySelector('[data-bookresa-booking-url]');
            const feedback = document.querySelector('[data-bookresa-copy-feedback]');

            if (!button || !input || !feedback) {
                return;
            }

            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(input.value);
                    feedback.textContent = @json(__('business_ui.url_copied'));
                } catch {
                    input.select();
                    feedback.textContent = @json(__('business_ui.copy_fallback'));
                }
            });
        });
    </script>
@endsection
