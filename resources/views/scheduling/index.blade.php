@extends('layouts.dashboard')

@section('title', __('app.scheduling_ui.scheduling').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.scheduling_ui.scheduling'))

@section('content')
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

        $configuredBusinessHours = $businessHours->filter(fn ($hour) => ! $hour->is_closed)->count();
        $openBusinessHours = $businessHours->where(fn ($hour) => ! $hour->is_closed)->count();
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.scheduling_ui.workspace_operations') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.scheduling_ui.scheduling') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.page_help') }}</p>
            </div>

            @can('services.view')
                <a href="{{ route('services.index') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.scheduling_ui.review_services') }}
                </a>
            @endcan
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('app.scheduling_ui.summary') }}">
            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.timezone') }}</p>
                <p class="mt-2 truncate text-lg font-bold text-slate-950 dark:text-white">{{ $tenant->profile?->timezone ?? 'UTC' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.scheduling_ui.timezone_help') }}</p>
            </article>

            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.business_hours') }}</p>
                <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $openBusinessHours }} / 7</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.scheduling_ui.open_days') }}</p>
            </article>

            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.staff') }}</p>
                <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ number_format($staffMembers->count()) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.scheduling_ui.staff_configurable') }}</p>
            </article>

            <article class="br-panel p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.exceptions') }}</p>
                <p class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ $businessHolidays->count() + $specialWorkingHours->count() + $businessBreaks->count() }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('app.scheduling_ui.exceptions_help') }}</p>
            </article>
        </section>

        <section class="br-panel overflow-hidden">
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
                <form method="POST" action="{{ route('scheduling.business-hours.update') }}" class="p-5 sm:p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-2">
                        @foreach ($days as $day)
                            @php
                                $hour = $businessHours->get($day->value);
                                $open = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                                $close = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                            @endphp

                            <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800 md:grid-cols-[8rem_1fr_1fr_auto] md:items-center">
                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">

                                <div>
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __($day->name) }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ $day->value }}</p>
                                </div>

                                <label class="text-xs font-semibold text-slate-500">
                                    <span class="mb-1.5 block">{{ __('app.scheduling_ui.opens') }}</span>
                                    <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>

                                <label class="text-xs font-semibold text-slate-500">
                                    <span class="mb-1.5 block">{{ __('app.scheduling_ui.closes') }}</span>
                                    <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                </label>

                                <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-800">
                                    <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($hour?->is_closed ?? true)>
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

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="br-panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.recurring') }}</p>
                        <h3 class="mt-2 text-lg font-bold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.breaks') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.breaks_help') }}</p>
                    </div>
                    <span class="rounded-xl bg-brand-soft px-2.5 py-1 text-xs font-bold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">{{ $businessBreaks->count() }}</span>
                </div>

                @can('settings.manage')
                    <form method="POST" action="{{ route('scheduling.breaks.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <select name="day_of_week" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($days as $day)
                                <option value="{{ $day->value }}">{{ __($day->name) }}</option>
                            @endforeach
                        </select>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="time" name="starts_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <input type="time" name="ends_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <input name="label" placeholder="{{ __('app.scheduling_ui.label_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.add_break') }}</button>
                    </form>
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
                                <form method="POST" action="{{ route('scheduling.breaks.destroy', $break) }}">
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
                    <form method="POST" action="{{ route('scheduling.holidays.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="date" name="holiday_date" required value="{{ old('holiday_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <input name="reason" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.save_holiday') }}</button>
                    </form>
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($businessHolidays as $holiday)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $holiday->holiday_date->format('Y-m-d') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $holiday->reason ?: __('app.scheduling_ui.business_closed') }}</p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.holidays.destroy', $holiday) }}">
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
                    <form method="POST" action="{{ route('scheduling.special-hours.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <input type="date" name="work_date" required value="{{ old('work_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <div class="grid grid-cols-2 gap-3">
                            <input type="time" name="opens_at" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <input type="time" name="closes_at" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <input name="reason" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_closed" value="1" class="rounded border-slate-300">
                            <span>{{ __('app.scheduling_ui.closed_all_day') }}</span>
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.save_special_hours') }}</button>
                    </form>
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
                                <form method="POST" action="{{ route('scheduling.special-hours.destroy', $special) }}">
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
        </section>

        <section class="br-panel overflow-hidden">
            <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.staff_schedule') }}</p>
                    <h3 class="mt-2 font-semibold text-slate-950 dark:text-white">{{ __('app.scheduling_ui.staff_schedule_title') }}</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.scheduling_ui.staff_schedule_help') }}</p>
                </div>

                @if ($staffMembers->isNotEmpty())
                    <form method="GET" action="{{ route('scheduling.index') }}" class="w-full sm:w-72">
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.scheduling_ui.staff_member') }}</label>
                        <select name="staff" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}" @selected($selectedStaff?->id === $staffMember->id)>{{ $staffMember->display_name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if ($selectedStaff)
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-base font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                                {{ str($selectedStaff->display_name)->substr(0, 1)->upper() }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-950 dark:text-white">{{ $selectedStaff->display_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $selectedStaff->user?->email }}</p>
                            </div>
                        </div>
                        <span class="inline-flex w-fit rounded-full border {{ $selectedStaff->status->value === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }} px-2.5 py-1 text-xs font-bold">
                            {{ $selectedStaff->status->value === 'active' ? __('app.scheduling_ui.active') : __('app.scheduling_ui.inactive') }}
                        </span>
                    </div>

                    @can('settings.manage')
                        <form method="POST" action="{{ route('scheduling.staff-hours.update', $selectedStaff) }}" class="mt-5 space-y-2">
                            @csrf
                            @method('PUT')

                            @foreach ($days as $day)
                                @php
                                    $hour = $selectedStaffHours->get($day->value);
                                    $open = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                                    $close = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                                @endphp

                                <div class="grid gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800 md:grid-cols-[8rem_1fr_1fr_auto] md:items-center">
                                    <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">
                                    <p class="text-sm font-bold text-slate-950 dark:text-white">{{ __($day->name) }}</p>
                                    <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm dark:border-slate-800">
                                        <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($hour?->is_closed ?? true)>
                                        <span class="font-semibold">{{ __('app.scheduling_ui.closed') }}</span>
                                    </label>
                                </div>
                            @endforeach

                            <div class="flex justify-end pt-3">
                                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                                    {{ __('app.scheduling_ui.save_staff_hours') }}
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($days as $day)
                                @php $hour = $selectedStaffHours->get($day->value); @endphp
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                    <p class="text-xs font-bold text-slate-500">{{ __($day->name) }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $hour?->is_closed ? __('app.scheduling_ui.closed') : (($hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '—').' — '.($hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '—')) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endcan

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
                                <form method="POST" action="{{ route('scheduling.staff-days-off.store', $selectedStaff) }}" class="mt-5 space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="date" name="starts_on" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        <input type="date" name="ends_on" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    </div>
                                    <input name="reason" placeholder="{{ __('app.scheduling_ui.reason_optional') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.scheduling_ui.add_day_off') }}</button>
                                </form>
                            @endcan

                            <div class="mt-5 space-y-2">
                                @forelse ($selectedStaffDaysOff as $dayOff)
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $dayOff->starts_on->format('Y-m-d') }} — {{ $dayOff->ends_on->format('Y-m-d') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $dayOff->reason ?: __('app.scheduling_ui.time_off') }}</p>
                                        </div>
                                        @can('settings.manage')
                                            <form method="POST" action="{{ route('scheduling.staff-days-off.destroy', [$selectedStaff, $dayOff]) }}">
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
                                <form method="POST" action="{{ route('scheduling.staff-availability.store', $selectedStaff) }}" class="mt-5 space-y-3">
                                    @csrf
                                    <input type="date" name="available_date" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <div class="grid grid-cols-2 gap-3">
                                        <input type="time" name="starts_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        <input type="time" name="ends_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    </div>
                                    <button type="submit" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">{{ __('app.scheduling_ui.add_availability') }}</button>
                                </form>
                            @endcan

                            <div class="mt-5 space-y-2">
                                @forelse ($selectedStaffAvailability as $availability)
                                    <div class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-950 dark:text-white">{{ $availability->available_date->format('Y-m-d') }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ substr((string) $availability->starts_at, 0, 5) }} — {{ substr((string) $availability->ends_at, 0, 5) }}</p>
                                        </div>
                                        @can('settings.manage')
                                            <form method="POST" action="{{ route('scheduling.staff-availability.destroy', [$selectedStaff, $availability]) }}">
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
                                <a href="{{ route('staff.index') }}" class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">{{ __('app.scheduling_ui.add_staff') }}</a>
                            @endif
                        @endcan
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
