@extends('layouts.dashboard')

@section('title', __('app.scheduling_ui.scheduling').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.scheduling_ui.scheduling'))

@section('content')
    <x-onboarding-progress :current="3" />

    @php
        use App\Domain\Scheduling\Enums\DayOfWeek;

        $days = [
            DayOfWeek::Monday,
            DayOfWeek::Tuesday,
            DayOfWeek::Wednesday,
            DayOfWeek::Thursday,
            DayOfWeek::Friday,
            DayOfWeek::Saturday,
            DayOfWeek::Sunday,
        ];

        $openBusinessHours = $businessHours->filter(fn ($hour) => ! $hour->is_closed)->count();

        $submittedHours = collect(old('hours', []));
        $firstSubmittedOpen = $submittedHours->first(fn ($hours) => is_array($hours)
            && ! (bool) data_get($hours, 'is_closed', false)
            && filled(data_get($hours, 'opens_at'))
            && filled(data_get($hours, 'closes_at')));
        $firstSavedOpen = $businessHours->first(fn ($hour) => ! $hour->is_closed && $hour->opens_at && $hour->closes_at);
        $defaultTemplateHours = $firstSubmittedOpen ?? $firstSavedOpen;
        $templateOpen = old('bulk_hours.opens_at', $defaultTemplateHours ? substr((string) data_get($defaultTemplateHours, 'opens_at'), 0, 5) : '09:00');
        $templateClose = old('bulk_hours.closes_at', $defaultTemplateHours ? substr((string) data_get($defaultTemplateHours, 'closes_at'), 0, 5) : '17:00');
        $selectedBulkStaffIds = collect(old('staff_ids', []))->map(fn ($id) => (string) $id)->all();
        $businessHoursOld = old('schedule_form') === 'business';
        $activeSchedulePanel = $openBusinessHours > 0 && $staffMembers->isNotEmpty() ? 'staff-schedule' : 'business-hours';
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.scheduling_ui.page_help') }}</p>
                <p class="mt-2 text-xs font-medium text-slate-400">{{ __('app.scheduling_ui.timezone') }}: <span class="font-bold text-slate-600 dark:text-slate-300">{{ $timezone }}</span></p>
            </div>
            @can('services.view')
                <a href="{{ route('services.index', ['tenant' => $tenant->slug]) }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.scheduling_ui.review_services') }}
                </a>
            @endcan
        </section>
        @if (! (bool) data_get($tenant->settings ?? [], 'onboarding.completed', false))
            <section class="rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-4 text-sm leading-6 text-indigo-950 dark:border-indigo-900/70 dark:bg-indigo-950/30 dark:text-indigo-100" role="note">
                <p class="font-bold">{{ __('app.scheduling_ui.onboarding_hours_title') }}</p>
                <p class="mt-1">{{ __('app.scheduling_ui.onboarding_hours_help') }}</p>
            </section>
        @endif
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="flex items-baseline gap-2"><span class="text-slate-500">{{ __('app.scheduling_ui.business_hours') }}</span><strong class="text-slate-900 dark:text-white">{{ $openBusinessHours }} / 7</strong></span>
            <span class="hidden h-4 border-s border-slate-200 sm:block dark:border-slate-700" aria-hidden="true"></span>
            <span class="flex items-baseline gap-2"><span class="text-slate-500">{{ __('app.scheduling_ui.staff') }}</span><strong class="text-slate-900 dark:text-white">{{ number_format($staffMembers->count()) }}</strong></span>
            <span class="hidden h-4 border-s border-slate-200 sm:block dark:border-slate-700" aria-hidden="true"></span>
            <span class="flex items-baseline gap-2"><span class="text-slate-500">{{ __('app.scheduling_ui.exceptions') }}</span><strong class="text-slate-900 dark:text-white">{{ $businessHolidays->count() + $specialWorkingHours->count() + $businessBreaks->count() }}</strong></span>
        </div>
        <nav aria-label="{{ __('app.scheduling_ui.summary') }}" role="tablist" class="grid grid-cols-1 gap-1 rounded-2xl border border-slate-200 bg-slate-100 p-1 sm:grid-cols-3 dark:border-slate-800 dark:bg-slate-950">
            <button id="scheduling-tab-business-hours" type="button" role="tab" aria-controls="business-hours" aria-selected="{{ $activeSchedulePanel === 'business-hours' ? 'true' : 'false' }}" data-schedule-tab="business-hours" data-active="{{ $activeSchedulePanel === 'business-hours' ? 'true' : 'false' }}" class="flex min-h-11 items-center justify-center rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $activeSchedulePanel === 'business-hours' ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-800 dark:text-indigo-300' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ __('app.scheduling_ui.business_working_hours') }}</button>
            <button id="scheduling-tab-business-exceptions" type="button" role="tab" aria-controls="business-exceptions" aria-selected="{{ $activeSchedulePanel === 'business-exceptions' ? 'true' : 'false' }}" data-schedule-tab="business-exceptions" data-active="{{ $activeSchedulePanel === 'business-exceptions' ? 'true' : 'false' }}" class="flex min-h-11 items-center justify-center rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $activeSchedulePanel === 'business-exceptions' ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-800 dark:text-indigo-300' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ __('app.scheduling_ui.exceptions') }}</button>
            <button id="scheduling-tab-staff-schedule" type="button" role="tab" aria-controls="staff-schedule" aria-selected="{{ $activeSchedulePanel === 'staff-schedule' ? 'true' : 'false' }}" data-schedule-tab="staff-schedule" data-active="{{ $activeSchedulePanel === 'staff-schedule' ? 'true' : 'false' }}" class="flex min-h-11 items-center justify-center rounded-xl px-3 py-2.5 text-sm font-bold transition {{ $activeSchedulePanel === 'staff-schedule' ? 'bg-white text-indigo-700 shadow-sm dark:bg-slate-800 dark:text-indigo-300' : 'text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-800' }}">{{ __('app.scheduling_ui.staff_schedule') }}</button>
        </nav>
        <section id="business-hours" data-schedule-panel="business-hours" @if ($activeSchedulePanel !== 'business-hours') hidden @endif role="tabpanel" aria-labelledby="scheduling-tab-business-hours" class="br-panel overflow-hidden scroll-mt-6">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.base_schedule') }}</p>
                    <h3 class="mt-2 font-semibold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.business_working_hours') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.scheduling_ui.business_working_hours_help') }}</p>
                </div>
                @unless ($openBusinessHours > 0)
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200">
                        {{ __('app.scheduling_ui.configure_hours') }}
                    </span>
                @endunless
            </div>

            @can('settings.manage')
                <form method="POST" action="{{ route('scheduling.business-hours.update', ['tenant' => $tenant->slug]) }}" data-business-hours-form class="p-5 sm:p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="schedule_form" value="business">

                    <details class="mb-5 rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 marker:text-slate-400 dark:text-indigo-300">{{ __('app.scheduling_ui.bulk_hours_title') }}</summary>
                    <section
                        data-hours-bulk-tools
                        data-invalid-message="{{ __('app.scheduling_ui.bulk_hours_invalid') }}"
                        data-no-open-days-message="{{ __('app.scheduling_ui.bulk_hours_no_open_days') }}"
                        data-applied-all-message="{{ __('app.scheduling_ui.bulk_hours_applied_all') }}"
                        data-applied-open-message="{{ __('app.scheduling_ui.bulk_hours_applied_open') }}"
                        class="mt-3"
                        aria-label="{{ __('app.scheduling_ui.bulk_hours_title') }}"
                    >
                        <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('app.scheduling_ui.bulk_hours_help') }}</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.opens') }}</span>
                                <input data-bulk-hours-open type="time" name="bulk_hours[opens_at]" value="{{ $templateOpen }}"
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </label>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.closes') }}</span>
                                <input data-bulk-hours-close type="time" name="bulk_hours[closes_at]" value="{{ $templateClose }}"
                                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </label>
                            <div class="flex flex-col gap-2 sm:col-span-2 lg:col-span-1">
                                <button type="button" data-apply-open-days
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-bold text-indigo-800 hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-900 dark:text-indigo-200 dark:hover:bg-slate-800">
                                    {{ __('app.scheduling_ui.bulk_hours_open_days') }}
                                </button>
                                <button type="button" data-apply-all-days
                                        class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                                    {{ __('app.scheduling_ui.bulk_hours_all_days') }}
                                </button>
                            </div>
                        </div>

                        <p data-bulk-hours-feedback class="mt-3 text-sm" role="status" aria-live="polite"></p>
                    </section>
                    </details>

                    <div class="space-y-3">
                        @foreach ($days as $day)
                            @php
                                $hour = $businessHours->get($day->value);
                                $savedOpen = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                                $open = $businessHoursOld ? old('hours.'.$loop->index.'.opens_at', $savedOpen) : $savedOpen;
                                $savedClose = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                                $close = $businessHoursOld ? old('hours.'.$loop->index.'.closes_at', $savedClose) : $savedClose;
                                $isClosed = $businessHoursOld
                                    ? (bool) old('hours.'.$loop->index.'.is_closed', false)
                                    : ($hour?->is_closed ?? true);
                            @endphp

                            <div data-hours-row class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:p-4 dark:border-slate-800 md:grid-cols-[8rem_1fr_1fr_auto] md:items-center">
                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">

                                <div>
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __($day->name) }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $day->value }}</p>
                                </div>

                                <label class="text-xs font-semibold text-slate-500">
                                    <span class="mb-1.5 block">{{ __('app.scheduling_ui.opens') }}</span>
                                    <input data-hours-time type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>

                                <label class="text-xs font-semibold text-slate-500">
                                    <span class="mb-1.5 block">{{ __('app.scheduling_ui.closes') }}</span>
                                    <input data-hours-time type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>

                                <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-800">
                                    <input data-hours-closed type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($isClosed)>
                                    <span class="font-semibold">{{ __('app.scheduling_ui.closed') }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-600">
                            {{ __('app.scheduling_ui.save_business_hours') }}
                        </button>
                    </div>
                </form>
            @else
                <div class="grid divide-y divide-slate-200 dark:divide-slate-800 sm:grid-cols-2 sm:divide-x sm:divide-y-0 lg:grid-cols-4">
                    @foreach ($days as $day)
                        @php $hour = $businessHours->get($day->value); @endphp
                        <div class="flex items-center justify-between gap-3 px-5 py-4">
                            <span class="text-sm font-semibold text-slate-950 dark:text-white">{{ __($day->name) }}</span>
                            <span class="text-xs font-semibold text-slate-500">
                                {{ $hour?->is_closed ? __('app.scheduling_ui.closed') : (($hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '—').' — '.($hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '—')) }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 px-5 py-4 text-sm text-slate-500 dark:border-slate-800">
                    {{ __('app.scheduling_ui.read_only_schedule') }}
                </div>
            @endcan
        </section>

        <details id="business-exceptions" data-schedule-panel="business-exceptions" @if ($activeSchedulePanel !== 'business-exceptions') hidden @endif @if ($activeSchedulePanel === 'business-exceptions') open @endif role="tabpanel" aria-labelledby="scheduling-tab-business-exceptions" class="group scroll-mt-6 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <summary @if ($activeSchedulePanel === 'business-exceptions') hidden @endif class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 sm:px-5">
                <span class="flex min-w-0 items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-extrabold text-slate-600 dark:bg-slate-800 dark:text-slate-300">02</span>
                    <span class="min-w-0">
                        <span class="block font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.exceptions') }}</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.exceptions_help') }}</span>
                        <span class="mt-2 block text-xs font-semibold text-slate-400">{{ $businessBreaks->count() }} {{ __('app.scheduling_ui.breaks') }} · {{ $businessHolidays->count() }} {{ __('app.scheduling_ui.holidays') }} · {{ $specialWorkingHours->count() }} {{ __('app.scheduling_ui.special_hours') }}</span>
                    </span>
                </span>
                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="space-y-4 border-t border-slate-200 p-4 sm:p-5 dark:border-slate-800">
            <article class="br-panel p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.recurring') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.breaks') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.breaks_help') }}</p>
                    </div>
                    <span class="rounded-xl bg-brand-soft px-2.5 py-1 text-xs font-bold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">{{ $businessBreaks->count() }}</span>
                </div>

                @can('settings.manage')
                    <details class="mt-4 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.add_break') }}</summary>
<form method="POST" action="{{ route('scheduling.breaks.store', ['tenant' => $tenant->slug]) }}" class="mt-5 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.day_of_week') }}</span>
                            <select name="day_of_week" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach ($days as $day)
                                    <option value="{{ $day->value }}" @selected((string) old('day_of_week', $days[0]->value) === (string) $day->value)>{{ __($day->name) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-slate-500">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.starts_at') }}</span>
                                <input type="time" name="starts_at" required value="{{ old('starts_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="block text-xs font-semibold text-slate-500">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.ends_at') }}</span>
                                <input type="time" name="ends_at" required value="{{ old('ends_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            </label>
                        </div>
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.label_optional') }}</span>
                            <input name="label" value="{{ old('label') }}" placeholder="{{ __('app.scheduling_ui.label_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.add_break') }}</button>
                    </form>
                    </details>
                
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($businessBreaks as $break)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ __($days[$break->day_of_week - 1]->name) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ substr((string) $break->starts_at, 0, 5) }} — {{ substr((string) $break->ends_at, 0, 5) }}</p>
                                @if ($break->label)
                                    <p class="mt-1 truncate text-xs text-slate-400">{{ $break->label }}</p>
                                @endif
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.breaks.destroy', ['tenant' => $tenant->slug, 'break' => $break]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-xs font-bold text-rose-700 hover:underline dark:text-rose-300">{{ __('app.scheduling_ui.remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">
                            {{ __('app.scheduling_ui.no_breaks') }}
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="br-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.one_off') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.holidays') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.holidays_help') }}</p>
                    </div>
                    <span class="rounded-xl bg-brand-soft px-2.5 py-1 text-xs font-bold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">{{ $businessHolidays->count() }}</span>
                </div>

                @can('settings.manage')
                    <details class="mt-4 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.save_holiday') }}</summary>
<form method="POST" action="{{ route('scheduling.holidays.store', ['tenant' => $tenant->slug]) }}" class="mt-5 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.holiday_date') }}</span>
                            <input type="date" name="holiday_date" required min="{{ $today }}" value="{{ old('holiday_date', $today) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.reason_optional') }}</span>
                            <input name="reason" value="{{ old('reason') }}" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.save_holiday') }}</button>
                    </form>
                    </details>
                
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($businessHolidays as $holiday)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $holiday->holiday_date->format('Y-m-d') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $holiday->reason ?: __('app.scheduling_ui.business_closed') }}</p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.holidays.destroy', ['tenant' => $tenant->slug, 'holiday' => $holiday]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-xs font-bold text-rose-700 hover:underline dark:text-rose-300">{{ __('app.scheduling_ui.remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">
                            {{ __('app.scheduling_ui.no_holidays') }}
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="br-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.one_off') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.special_hours') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.special_hours_help') }}</p>
                    </div>
                    <span class="rounded-xl bg-brand-soft px-2.5 py-1 text-xs font-bold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">{{ $specialWorkingHours->count() }}</span>
                </div>

                @can('settings.manage')
                    <details class="mt-4 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.save_special_hours') }}</summary>
<form method="POST" action="{{ route('scheduling.special-hours.store', ['tenant' => $tenant->slug]) }}" data-special-hours-form class="mt-5 space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.work_date') }}</span>
                            <input type="date" name="work_date" required min="{{ $today }}" value="{{ old('work_date', $today) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-xs font-semibold text-slate-500">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.opens') }}</span>
                                <input data-special-time type="time" name="opens_at" value="{{ old('opens_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="block text-xs font-semibold text-slate-500">
                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.closes') }}</span>
                                <input data-special-time type="time" name="closes_at" value="{{ old('closes_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            </label>
                        </div>
                        <label class="block text-xs font-semibold text-slate-500">
                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.reason_optional') }}</span>
                            <input name="reason" value="{{ old('reason') }}" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input data-special-closed type="checkbox" name="is_closed" value="1" @checked((bool) old('is_closed', false)) class="rounded border-slate-300">
                            <span>{{ __('app.scheduling_ui.closed_all_day') }}</span>
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.save_special_hours') }}</button>
                    </form>
                    </details>
                
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($specialWorkingHours as $special)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $special->work_date->format('Y-m-d') }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $special->is_closed ? __('app.scheduling_ui.closed') : (substr((string) $special->opens_at, 0, 5).' — '.substr((string) $special->closes_at, 0, 5)) }}
                                    @if ($special->reason) · {{ $special->reason }} @endif
                                </p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.special-hours.destroy', ['tenant' => $tenant->slug, 'specialWorkingHour' => $special]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="shrink-0 text-xs font-bold text-rose-700 hover:underline dark:text-rose-300">{{ __('app.scheduling_ui.remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">
                            {{ __('app.scheduling_ui.no_special_hours') }}
                        </div>
                    @endforelse
                </div>
            </article>
            </div>
        </details>

        <details id="staff-schedule" data-schedule-panel="staff-schedule" @if ($activeSchedulePanel !== 'staff-schedule') hidden @endif @if ($activeSchedulePanel === 'staff-schedule') open @endif role="tabpanel" aria-labelledby="scheduling-tab-staff-schedule" class="group scroll-mt-6 rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <summary @if ($activeSchedulePanel === 'staff-schedule') hidden @endif class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-4 sm:px-5">
                <span class="flex min-w-0 items-start gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-extrabold text-slate-600 dark:bg-slate-800 dark:text-slate-300">03</span>
                    <span class="min-w-0">
                        <span class="block font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.staff_schedule_title') }}</span>
                        <span class="mt-1 block text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.staff_schedule_help') }}</span>
                    </span>
                </span>
                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="border-t border-slate-200 dark:border-slate-800">
                @can('settings.manage')
                    <details class="m-4 rounded-xl border border-slate-200 dark:border-slate-800">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.bulk_staff_hours_optional_title') }}</summary>
                    <section class="border-t border-slate-200 p-4 sm:p-5 dark:border-slate-800" aria-labelledby="bulk-staff-hours-heading">
                        <div class="max-w-3xl">
                            <h4 id="bulk-staff-hours-heading" class="text-base font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.bulk_staff_hours_title') }}</h4>
                            <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.scheduling_ui.bulk_staff_hours_help') }}</p>
                        </div>

                        <form method="POST"
                              action="{{ route('scheduling.staff-hours.bulk', ['tenant' => $tenant->slug]) }}"
                              data-bulk-staff-hours-form
                              data-count-label="{{ __('app.scheduling_ui.bulk_staff_selected_count', ['count' => '__COUNT__']) }}"
                              data-select-staff-message="{{ __('app.scheduling_ui.choose_staff_for_bulk') }}"
                              class="mt-5 space-y-5">
                            @csrf
                            <input type="hidden" name="schedule_form" value="bulk">

                            <fieldset class="space-y-3">
                                <legend class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-100">{{ __('app.scheduling_ui.select_staff_for_bulk') }}</legend>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" data-select-active-staff class="inline-flex min-h-10 items-center justify-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                        {{ __('app.scheduling_ui.select_all_active_staff') }}
                                    </button>
                                    <button type="button" data-clear-staff-selection class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                        {{ __('app.scheduling_ui.clear_staff_selection') }}
                                    </button>
                                    <p data-staff-selection-count class="flex min-h-10 items-center px-1 text-sm font-semibold text-slate-500" role="status" aria-live="polite"></p>
                                </div>

                                @if ($staffMembers->isNotEmpty())
                                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach ($staffMembers as $staffMember)
                                            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2.5 transition hover:border-indigo-300 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/60 dark:border-slate-800 dark:hover:border-indigo-700 dark:has-[:checked]:border-indigo-700 dark:has-[:checked]:bg-indigo-950/30">
                                                <input type="checkbox"
                                                       name="staff_ids[]"
                                                       value="{{ $staffMember->id }}"
                                                       data-bulk-staff-id
                                                       data-staff-status="{{ $staffMember->status->value }}"
                                                       @checked(in_array((string) $staffMember->id, $selectedBulkStaffIds, true))
                                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ $staffMember->display_name }}</span>
                                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $staffMember->status->value === 'active' ? __('app.scheduling_ui.active') : __('app.scheduling_ui.inactive') }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="rounded-xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500 dark:border-slate-700">{{ __('app.scheduling_ui.add_staff_first') }}</p>
                                @endif

                                @if ($errors->has('staff_ids') || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'staff_ids.')))
                                    <p class="text-sm font-semibold text-rose-700 dark:text-rose-300" role="alert">{{ $errors->first('staff_ids') ?: __('app.scheduling_ui.choose_staff_for_bulk') }}</p>
                                @endif
                            </fieldset>

                            <div>
                                <div class="mb-3">
                                    <h5 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ __('app.scheduling_ui.business_working_hours') }}</h5>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('app.scheduling_ui.bulk_staff_hours_template_help') }}</p>
                                </div>
                                <div class="grid gap-3">
                                    @foreach ($days as $day)
                                        @php
                                            $baseHour = $businessHours->get($day->value);
                                            $bulkSavedOpen = $baseHour && ! $baseHour->is_closed && $baseHour->opens_at ? substr((string) $baseHour->opens_at, 0, 5) : '';
                                            $bulkOpen = old('schedule_form') === 'bulk' ? old('hours.'.$loop->index.'.opens_at', $bulkSavedOpen) : $bulkSavedOpen;
                                            $bulkSavedClose = $baseHour && ! $baseHour->is_closed && $baseHour->closes_at ? substr((string) $baseHour->closes_at, 0, 5) : '';
                                            $bulkClose = old('schedule_form') === 'bulk' ? old('hours.'.$loop->index.'.closes_at', $bulkSavedClose) : $bulkSavedClose;
                                            $bulkClosed = old('schedule_form') === 'bulk'
                                                ? (bool) old('hours.'.$loop->index.'.is_closed', false)
                                                : ($baseHour?->is_closed ?? true);
                                        @endphp
                                        <div data-hours-row class="grid gap-3 rounded-xl border border-slate-200 p-3 sm:grid-cols-[8rem_1fr_1fr_auto] sm:items-center dark:border-slate-800">
                                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">
                                            <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __($day->name) }}</p>
                                            <label class="block text-xs font-semibold text-slate-500">
                                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.opens') }}</span>
                                                <input data-hours-time type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $bulkOpen }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                            </label>
                                            <label class="block text-xs font-semibold text-slate-500">
                                                <span class="mb-1.5 block">{{ __('app.scheduling_ui.closes') }}</span>
                                                <input data-hours-time type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $bulkClose }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                            </label>
                                            <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-800">
                                                <input data-hours-closed type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($bulkClosed)>
                                                <span class="font-semibold">{{ __('app.scheduling_ui.closed') }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if (old('schedule_form') === 'bulk' && $errors->has('schedule'))
                                <p class="text-sm font-semibold text-rose-700 dark:text-rose-300" role="alert">{{ $errors->first('schedule') }}</p>
                            @endif

                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                                <p class="max-w-xl text-xs leading-5 text-slate-500">{{ __('app.scheduling_ui.bulk_staff_hours_template_help') }}</p>
                                <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-600 disabled:cursor-not-allowed disabled:opacity-50">
                                    {{ __('app.scheduling_ui.save_bulk_staff_hours') }}
                                </button>
                            </div>
                        </form>
                    </section>
                    </details>
                @endcan

                <section class="border-b border-slate-200 p-4 sm:p-5 dark:border-slate-800" aria-labelledby="individual-staff-schedules-heading">
                    <div class="max-w-3xl">
                        <h4 id="individual-staff-schedules-heading" class="text-base font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.weekly_schedule') }}</h4>
                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ __('app.scheduling_ui.staff_schedule_help') }}</p>
                    </div>

                    @if ($staffMembers->isNotEmpty())
                        <div class="mt-4 space-y-4">
                            @foreach ($staffMembers as $staffMember)
                                @php
                                    $staffHours = $staffMember->workingHours->keyBy(fn ($hour): int => $hour->day_of_week->value);
                                    $isEditingStaffSchedule = old('schedule_form') === 'staff'
                                        && (string) old('staff_schedule_id') === (string) $staffMember->id;
                                @endphp

                                <article data-doctor-schedule class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                                    <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-sm font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                                                {{ str($staffMember->display_name)->substr(0, 1)->upper() }}
                                            </div>
                                            <div class="min-w-0">
                                                <h5 class="truncate font-bold text-slate-950 dark:text-white">{{ $staffMember->display_name }}</h5>
                                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $staffMember->user?->email }}</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex w-fit rounded-full border {{ $staffMember->status->value === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }} px-2.5 py-1 text-xs font-bold">
                                            {{ $staffMember->status->value === 'active' ? __('app.scheduling_ui.active') : __('app.scheduling_ui.inactive') }}
                                        </span>
                                    </div>

                                    @can('settings.manage')
                                        <form method="POST" action="{{ route('scheduling.staff-hours.update', ['tenant' => $tenant->slug, 'staff' => $staffMember]) }}" class="p-3 sm:p-4">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="schedule_form" value="staff">
                                            <input type="hidden" name="staff_schedule_id" value="{{ $staffMember->id }}">
                                    @endcan

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                                                <tr>
                                                    <th scope="col" class="whitespace-nowrap px-3 py-3 text-start">{{ __('app.scheduling_ui.day_of_week') }}</th>
                                                    <th scope="col" class="whitespace-nowrap px-3 py-3 text-start">{{ __('app.scheduling_ui.opens') }}</th>
                                                    <th scope="col" class="whitespace-nowrap px-3 py-3 text-start">{{ __('app.scheduling_ui.closes') }}</th>
                                                    <th scope="col" class="whitespace-nowrap px-3 py-3 text-start">{{ __('app.scheduling_ui.closed') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                                @foreach ($days as $day)
                                                    @php
                                                        $hour = $staffHours->get($day->value);
                                                        $savedOpen = $hour && ! $hour->is_closed && $hour->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                                                        $savedClose = $hour && ! $hour->is_closed && $hour->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                                                        $open = $isEditingStaffSchedule ? old('hours.'.$loop->index.'.opens_at', $savedOpen) : $savedOpen;
                                                        $close = $isEditingStaffSchedule ? old('hours.'.$loop->index.'.closes_at', $savedClose) : $savedClose;
                                                        $isClosed = $isEditingStaffSchedule
                                                            ? (bool) old('hours.'.$loop->index.'.is_closed', false)
                                                            : ($hour?->is_closed ?? true);
                                                    @endphp
                                                    <tr data-hours-row>
                                                        <th scope="row" class="whitespace-nowrap px-3 py-3 text-start font-semibold text-slate-800 dark:text-slate-200">{{ __($day->name) }}</th>
                                                        @can('settings.manage')
                                                            <td class="min-w-36 px-3 py-2">
                                                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">
                                                                <input data-hours-time type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}" aria-label="{{ __($day->name) }} — {{ __('app.scheduling_ui.opens') }}" class="w-full min-w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                                            </td>
                                                            <td class="min-w-36 px-3 py-2">
                                                                <input data-hours-time type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}" aria-label="{{ __($day->name) }} — {{ __('app.scheduling_ui.closes') }}" class="w-full min-w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                                            </td>
                                                            <td class="px-3 py-2">
                                                                <label class="flex min-h-10 items-center gap-2 whitespace-nowrap rounded-lg px-2 text-sm">
                                                                    <input data-hours-closed type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($isClosed) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                                    <span class="font-semibold">{{ __('app.scheduling_ui.closed') }}</span>
                                                                </label>
                                                            </td>
                                                        @else
                                                            <td class="whitespace-nowrap px-3 py-3 text-slate-600 dark:text-slate-300">{{ $isClosed ? '—' : ($open ?: '—') }}</td>
                                                            <td class="whitespace-nowrap px-3 py-3 text-slate-600 dark:text-slate-300">{{ $isClosed ? '—' : ($close ?: '—') }}</td>
                                                            <td class="whitespace-nowrap px-3 py-3 text-slate-500 dark:text-slate-400">{{ $isClosed ? __('app.scheduling_ui.closed') : '—' }}</td>
                                                        @endcan
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @can('settings.manage')
                                            @if ($isEditingStaffSchedule && $errors->any())
                                                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-200" role="alert">
                                                    @foreach ($errors->all() as $error)
                                                        <p>{{ $error }}</p>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="mt-4 flex justify-end">
                                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-600">
                                                    {{ __('app.scheduling_ui.save_staff_hours') }}
                                                </button>
                                            </div>
                                        </form>
                                    @endcan
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('app.scheduling_ui.add_staff_first') }}</div>
                    @endif
                </section>

                <details class="m-4 rounded-xl border border-slate-200 dark:border-slate-800">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.manage_staff_exceptions_title') }}</summary>
                    <div class="border-t border-slate-200 dark:border-slate-800">
                        <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-5">


                @if ($staffMembers->isNotEmpty())
                    <form method="GET" action="{{ route('scheduling.index', ['tenant' => $tenant->slug]) }}" class="w-full sm:w-72">
                        <label for="scheduling-staff-select" class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.staff_member') }}</label>
                        <select id="scheduling-staff-select" name="staff" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}" @selected($selectedStaff?->id === $staffMember->id)>{{ $staffMember->display_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if ($selectedStaff)
                <div class="p-5 sm:p-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <h4 class="font-bold text-slate-950 dark:text-white">{{ $selectedStaff->display_name }}</h4>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.staff_exceptions_help') }}</p>
                    </div>

                    <div class="mt-7 grid gap-5 lg:grid-cols-2">
                        <article class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.days_off') }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.days_off_help') }}</p>
                                </div>
                                <span class="rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $selectedStaffDaysOff->count() }}</span>
                            </div>

                            @can('settings.manage')
                    <details class="mt-4 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.add_day_off') }}</summary>
<form method="POST" action="{{ route('scheduling.staff-days-off.store', ['tenant' => $tenant->slug, 'staff' => $selectedStaff]) }}" class="mt-5 space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="block text-xs font-semibold text-slate-500">
                                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.starts_on') }}</span>
                                            <input type="date" name="starts_on" required min="{{ $today }}" value="{{ old('starts_on') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        </label>
                                        <label class="block text-xs font-semibold text-slate-500">
                                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.ends_on') }}</span>
                                            <input type="date" name="ends_on" required min="{{ $today }}" value="{{ old('ends_on') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        </label>
                                    </div>
                                    <input name="reason" value="{{ old('reason') }}" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.scheduling_ui.add_day_off') }}</button>
                                </form>
                    </details>
                
                            @endcan

                            <div class="mt-5 space-y-2">
                                @forelse ($selectedStaffDaysOff as $dayOff)
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $dayOff->starts_on->format('Y-m-d') }} — {{ $dayOff->ends_on->format('Y-m-d') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $dayOff->reason ?: __('app.scheduling_ui.time_off') }}</p>
                                        </div>
                                        @can('settings.manage')
                                            <form method="POST" action="{{ route('scheduling.staff-days-off.destroy', ['tenant' => $tenant->slug, 'staff' => $selectedStaff, 'dayOff' => $dayOff]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-bold text-rose-700 hover:underline dark:text-rose-300">{{ __('app.scheduling_ui.remove') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('app.scheduling_ui.no_days_off') }}</div>
                                @endforelse
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.explicit_availability') }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.explicit_availability_help') }}</p>
                                </div>
                                <span class="rounded-xl bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $selectedStaffAvailability->count() }}</span>
                            </div>

                            @can('settings.manage')
                    <details class="mt-4 rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                        <summary class="cursor-pointer text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ __('app.scheduling_ui.add_availability') }}</summary>
<form method="POST" action="{{ route('scheduling.staff-availability.store', ['tenant' => $tenant->slug, 'staff' => $selectedStaff]) }}" class="mt-5 space-y-3">
                                    @csrf
                                    <label class="block text-xs font-semibold text-slate-500">
                                        <span class="mb-1.5 block">{{ __('app.scheduling_ui.available_date') }}</span>
                                        <input type="date" name="available_date" required min="{{ $today }}" value="{{ old('available_date') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="block text-xs font-semibold text-slate-500">
                                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.starts_at') }}</span>
                                            <input type="time" name="starts_at" required value="{{ old('starts_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        </label>
                                        <label class="block text-xs font-semibold text-slate-500">
                                            <span class="mb-1.5 block">{{ __('app.scheduling_ui.ends_at') }}</span>
                                            <input type="time" name="ends_at" required value="{{ old('ends_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        </label>
                                    </div>
                                    <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.scheduling_ui.add_availability') }}</button>
                                </form>
                    </details>
                
                            @endcan

                            <div class="mt-5 space-y-2">
                                @forelse ($selectedStaffAvailability as $availability)
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $availability->available_date->format('Y-m-d') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ substr((string) $availability->starts_at, 0, 5) }} — {{ substr((string) $availability->ends_at, 0, 5) }}</p>
                                        </div>
                                        @can('settings.manage')
                                            <form method="POST" action="{{ route('scheduling.staff-availability.destroy', ['tenant' => $tenant->slug, 'staff' => $selectedStaff, 'availability' => $availability]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-bold text-rose-700 hover:underline dark:text-rose-300">{{ __('app.scheduling_ui.remove') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700">{{ __('app.scheduling_ui.no_availability') }}</div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>
            @else
                <div class="px-5 py-12">
                    <div class="mx-auto max-w-md text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" d="M4 5h16M4 10h16M4 15h9M4 20h6"/>
                            </svg>
                        </div>
                        <h4 class="mt-4 font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.no_staff_selected') }}</h4>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ $staffMembers->isEmpty() ? __('app.scheduling_ui.add_staff_first') : __('app.scheduling_ui.choose_staff') }}</p>
                        @can('staff.manage')
                            @if ($staffMembers->isEmpty())
                                <a href="{{ route('staff.index', ['tenant' => $tenant->slug]) }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.add_staff') }}</a>
                            @endif
                        @endcan
                    </div>
                </div>
            @endif
                        </div>
                    </details>
            </div>
        </details>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const hoursRows = [...document.querySelectorAll('[data-hours-row]')];
            const businessHoursForm = document.querySelector('[data-business-hours-form]');
            const businessHoursRows = businessHoursForm ? [...businessHoursForm.querySelectorAll('[data-hours-row]')] : [];
            const bulkStaffHoursForm = document.querySelector('[data-bulk-staff-hours-form]');
            if (bulkStaffHoursForm) {
                const staffCheckboxes = [...bulkStaffHoursForm.querySelectorAll('[data-bulk-staff-id]')];
                const countDisplay = bulkStaffHoursForm.querySelector('[data-staff-selection-count]');
                const countLabel = bulkStaffHoursForm.dataset.countLabel ?? '__COUNT__';
                const refreshStaffSelection = () => {
                    const selectedCount = staffCheckboxes.filter((checkbox) => checkbox.checked).length;
                    if (countDisplay) countDisplay.textContent = countLabel.replace('__COUNT__', String(selectedCount));
                };

                bulkStaffHoursForm.querySelector('[data-select-active-staff]')?.addEventListener('click', () => {
                    staffCheckboxes.forEach((checkbox) => {
                        if (checkbox.dataset.staffStatus === 'active') checkbox.checked = true;
                    });
                    refreshStaffSelection();
                });
                bulkStaffHoursForm.querySelector('[data-clear-staff-selection]')?.addEventListener('click', () => {
                    staffCheckboxes.forEach((checkbox) => { checkbox.checked = false; });
                    refreshStaffSelection();
                });
                staffCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', refreshStaffSelection));
                refreshStaffSelection();

                bulkStaffHoursForm.addEventListener('submit', (event) => {
                    const selectedCount = staffCheckboxes.filter((checkbox) => checkbox.checked).length;
                    if (selectedCount > 0) return;
                    event.preventDefault();
                    const fieldset = bulkStaffHoursForm.querySelector('fieldset');
                    let error = fieldset?.querySelector('[data-client-staff-error]');
                    if (!error && fieldset) {
                        error = document.createElement('p');
                        error.dataset.clientStaffError = 'true';
                        error.className = 'text-sm font-semibold text-rose-700 dark:text-rose-300';
                        error.setAttribute('role', 'alert');
                        fieldset.appendChild(error);
                    }
                    if (error) error.textContent = bulkStaffHoursForm.dataset.selectStaffMessage ?? '';
                    fieldset?.querySelector('input[type="checkbox"]')?.focus();
                });
            }
            const scheduleTabs = [...document.querySelectorAll('[data-schedule-tab]')];
            const schedulePanels = [...document.querySelectorAll('[data-schedule-panel]')];
            const activateSchedulePanel = (panelId) => {
                schedulePanels.forEach((panel) => {
                    const active = panel.dataset.schedulePanel === panelId;
                    panel.hidden = !active;

                    if (panel instanceof HTMLDetailsElement) {
                        panel.open = active;
                        const summary = panel.querySelector(':scope > summary');
                        if (summary) summary.hidden = active;
                    }
                });

                scheduleTabs.forEach((tab) => {
                    const active = tab.dataset.scheduleTab === panelId;
                    tab.dataset.active = active ? 'true' : 'false';
                    tab.classList.toggle('bg-white', active);
                    tab.classList.toggle('text-indigo-700', active);
                    tab.classList.toggle('shadow-sm', active);
                    tab.classList.toggle('dark:bg-slate-800', active);
                    tab.classList.toggle('dark:text-indigo-300', active);
                    tab.classList.toggle('text-slate-600', !active);
                    tab.classList.toggle('hover:bg-white', !active);
                    tab.classList.toggle('dark:text-slate-300', !active);
                    tab.classList.toggle('dark:hover:bg-slate-800', !active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.tabIndex = active ? 0 : -1;
                });
            };

            scheduleTabs.forEach((tab, index) => {
                tab.addEventListener('click', () => {
                    const panelId = tab.dataset.scheduleTab;
                    if (panelId) activateSchedulePanel(panelId);
                });

                tab.addEventListener('keydown', (event) => {
                    const rtl = document.documentElement.dir === 'rtl';
                    let nextIndex = null;

                    if (event.key === (rtl ? 'ArrowLeft' : 'ArrowRight') || event.key === 'ArrowDown') {
                        nextIndex = (index + 1) % scheduleTabs.length;
                    } else if (event.key === (rtl ? 'ArrowRight' : 'ArrowLeft') || event.key === 'ArrowUp') {
                        nextIndex = (index - 1 + scheduleTabs.length) % scheduleTabs.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = scheduleTabs.length - 1;
                    }

                    if (nextIndex === null) return;

                    event.preventDefault();
                    scheduleTabs[nextIndex].focus();
                    const panelId = scheduleTabs[nextIndex].dataset.scheduleTab;
                    if (panelId) activateSchedulePanel(panelId);
                });
            });
            activateSchedulePanel(@js($activeSchedulePanel));

            const syncHoursRow = (row) => {
                const closed = row.querySelector('[data-hours-closed]');
                const times = [...row.querySelectorAll('[data-hours-time]')];
                if (!closed) return;

                times.forEach((input) => {
                    input.disabled = closed.checked;
                    input.required = !closed.checked;
                });
            };

            hoursRows.forEach((row) => {
                const closed = row.querySelector('[data-hours-closed]');
                if (!closed) return;

                closed.addEventListener('change', () => syncHoursRow(row));
                syncHoursRow(row);
            });

            const bulkTools = document.querySelector('[data-hours-bulk-tools]');
            if (bulkTools) {
                const opensInput = bulkTools.querySelector('[data-bulk-hours-open]');
                const closesInput = bulkTools.querySelector('[data-bulk-hours-close]');
                const feedback = bulkTools.querySelector('[data-bulk-hours-feedback]');

                const readBulkTimes = () => {
                    const opensAt = opensInput?.value ?? '';
                    const closesAt = closesInput?.value ?? '';

                    if (!opensAt || !closesAt || closesAt <= opensAt) {
                        feedback.textContent = bulkTools.dataset.invalidMessage ?? '';
                        feedback.className = 'mt-3 text-sm font-semibold text-rose-700 dark:text-rose-300';

                        return null;
                    }

                    return { opensAt, closesAt };
                };

                const applyTimes = (includeClosedDays) => {
                    const times = readBulkTimes();
                    if (!times) return;

                    const targetRows = businessHoursRows.filter((row) => includeClosedDays || !row.querySelector('[data-hours-closed]')?.checked);

                    if (targetRows.length === 0) {
                        feedback.textContent = bulkTools.dataset.noOpenDaysMessage ?? '';
                        feedback.className = 'mt-3 text-sm font-semibold text-amber-700 dark:text-amber-300';

                        return;
                    }

                    targetRows.forEach((row) => {
                        const closed = row.querySelector('[data-hours-closed]');
                        const fields = [...row.querySelectorAll('[data-hours-time]')];

                        if (!closed || fields.length !== 2) return;

                        if (includeClosedDays) closed.checked = false;
                        syncHoursRow(row);
                        fields[0].value = times.opensAt;
                        fields[1].value = times.closesAt;
                    });

                    feedback.textContent = includeClosedDays
                        ? (bulkTools.dataset.appliedAllMessage ?? '')
                        : (bulkTools.dataset.appliedOpenMessage ?? '');
                    feedback.className = 'mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300';
                };

                bulkTools.querySelector('[data-apply-open-days]')?.addEventListener('click', () => applyTimes(false));
                bulkTools.querySelector('[data-apply-all-days]')?.addEventListener('click', () => applyTimes(true));

                [opensInput, closesInput].forEach((input) => input?.addEventListener('input', () => {
                    feedback.textContent = '';
                }));
            }

            document.querySelectorAll('[data-special-hours-form]').forEach((form) => {
                const closed = form.querySelector('[data-special-closed]');
                const times = [...form.querySelectorAll('[data-special-time]')];
                if (!closed) return;

                const sync = () => times.forEach((input) => {
                    input.disabled = closed.checked;
                    input.required = !closed.checked;
                });

                closed.addEventListener('change', sync);
                sync();
            });
        });
    </script>

@endsection