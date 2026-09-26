@extends('layouts.dashboard')

@section('title', __('Scheduling'))
@section('heading', __('Scheduling'))

@php
    $days = [
        1 => __('Monday'),
        2 => __('Tuesday'),
        3 => __('Wednesday'),
        4 => __('Thursday'),
        5 => __('Friday'),
        6 => __('Saturday'),
        7 => __('Sunday'),
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-base font-semibold">{{ __('Business working hours') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Set the regular opening hours for this workspace.') }}</p>
            </div>

            <form method="POST" action="{{ route('scheduling.business-hours.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    @foreach ($days as $day => $label)
                        @php $hour = $businessHours->get($day); @endphp
                        <div class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[1.2fr_1fr_1fr_auto] sm:items-center dark:border-slate-800">
                            <div class="font-medium">{{ $label }}</div>
                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day }}">
                            <label class="flex items-center gap-2 text-sm">
                                <span class="text-slate-500">{{ __('Opens') }}</span>
                                <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $hour?->opens_at ? substr($hour->opens_at, 0, 5) : '09:00' }}"
                                       class="w-full rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <span class="text-slate-500">{{ __('Closes') }}</span>
                                <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $hour?->closes_at ? substr($hour->closes_at, 0, 5) : '17:00' }}"
                                       class="w-full rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="hours[{{ $loop->index }}][is_closed]" value="0">
                                <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1"
                                       @checked($hour?->is_closed ?? false) class="rounded border-slate-300">
                                <span>{{ __('Closed') }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <button class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    {{ __('Save business hours') }}
                </button>
            </form>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold">{{ __('Recurring breaks') }}</h2>
                <form method="POST" action="{{ route('scheduling.breaks.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <select name="day_of_week" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($days as $day => $label)
                            <option value="{{ $day }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="label" value="{{ old('label') }}" placeholder="{{ __('Label') }}"
                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                    <input type="time" name="starts_at" value="{{ old('starts_at', '13:00') }}"
                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                    <input type="time" name="ends_at" value="{{ old('ends_at', '14:00') }}"
                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                    <button class="sm:col-span-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                        {{ __('Add break') }}
                    </button>
                </form>

                <div class="mt-5 space-y-2">
                    @forelse ($businessBreaks as $break)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-950">
                            <div>
                                <p class="text-sm font-medium">{{ $days[$break->day_of_week] ?? $break->day_of_week }}</p>
                                <p class="text-xs text-slate-500">{{ substr($break->starts_at, 0, 5) }} — {{ substr($break->ends_at, 0, 5) }}{{ $break->label ? ' · '.$break->label : '' }}</p>
                            </div>
                            <form method="POST" action="{{ route('scheduling.breaks.destroy', $break) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-medium text-rose-600 hover:text-rose-700">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No recurring breaks yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-base font-semibold">{{ __('Business holidays') }}</h2>
                <form method="POST" action="{{ route('scheduling.holidays.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_1.5fr_auto]">
                    @csrf
                    <input type="date" name="holiday_date" value="{{ old('holiday_date') }}"
                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                    <input name="reason" value="{{ old('reason') }}" placeholder="{{ __('Reason') }}"
                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                        {{ __('Save') }}
                    </button>
                </form>

                <div class="mt-5 space-y-2">
                    @forelse ($businessHolidays as $holiday)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-950">
                            <div>
                                <p class="text-sm font-medium">{{ $holiday->holiday_date->format('Y-m-d') }}</p>
                                <p class="text-xs text-slate-500">{{ $holiday->reason ?: __('Holiday') }}</p>
                            </div>
                            <form method="POST" action="{{ route('scheduling.holidays.destroy', $holiday) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-medium text-rose-600 hover:text-rose-700">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No holidays yet.') }}</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-base font-semibold">{{ __('Special working hours') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Override the regular business schedule for specific dates.') }}</p>
            </div>

            <form method="POST" action="{{ route('scheduling.special-hours.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @csrf
                <input type="date" name="work_date" value="{{ old('work_date') }}"
                       class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                <input type="time" name="opens_at" value="{{ old('opens_at', '09:00') }}"
                       class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                <input type="time" name="closes_at" value="{{ old('closes_at', '17:00') }}"
                       class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                <input name="reason" value="{{ old('reason') }}" placeholder="{{ __('Reason') }}"
                       class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm dark:border-slate-800">
                    <input type="hidden" name="is_closed" value="0">
                    <input type="checkbox" name="is_closed" value="1" @checked(old('is_closed')) class="rounded border-slate-300">
                    <span>{{ __('Closed') }}</span>
                </label>
                <button class="lg:col-span-5 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                    {{ __('Save special hours') }}
                </button>
            </form>

            <div class="mt-5 space-y-2">
                @forelse ($specialWorkingHours as $special)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-slate-950">
                        <div>
                            <p class="text-sm font-medium">{{ $special->work_date->format('Y-m-d') }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $special->is_closed ? __('Closed') : substr($special->opens_at, 0, 5).' — '.substr($special->closes_at, 0, 5) }}
                                {{ $special->reason ? ' · '.$special->reason : '' }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('scheduling.special-hours.destroy', $special) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-medium text-rose-600 hover:text-rose-700">{{ __('Remove') }}</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('No special hours yet.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-base font-semibold">{{ __('Staff schedules') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Manage individual working hours, days off, and explicit availability.') }}</p>
            </div>

            @forelse ($staffMembers as $staff)
                @php
                    $staffHours = $staff->workingHours->keyBy(fn ($hour) => $hour->day_of_week->value);
                    $daysOff = $staff->daysOff;
                    $availability = $staff->availability;
                @endphp

                <div class="mb-6 rounded-2xl border border-slate-200 p-4 last:mb-0 dark:border-slate-800">
                    <div class="mb-4">
                        <h3 class="font-semibold">{{ $staff->display_name ?: $staff->user?->name }}</h3>
                        <p class="text-xs text-slate-500">{{ $staff->user?->email }}</p>
                    </div>

                    <form method="POST" action="{{ route('scheduling.staff.hours.update', $staff) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-2">
                            @foreach ($days as $day => $label)
                                @php $hour = $staffHours->get($day); @endphp
                                <div class="grid gap-2 sm:grid-cols-[1.2fr_1fr_1fr_auto] sm:items-center">
                                    <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day }}">
                                    <span class="text-sm font-medium">{{ $label }}</span>
                                    <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $hour?->opens_at ? substr($hour->opens_at, 0, 5) : '09:00' }}"
                                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                    <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $hour?->closes_at ? substr($hour->closes_at, 0, 5) : '17:00' }}"
                                           class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="hidden" name="hours[{{ $loop->index }}][is_closed]" value="0">
                                        <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1"
                                               @checked($hour?->is_closed ?? false) class="rounded border-slate-300">
                                        <span>{{ __('Closed') }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                            {{ __('Save staff hours') }}
                        </button>
                    </form>

                    <div class="mt-6 grid gap-6 xl:grid-cols-2">
                        <div>
                            <h4 class="text-sm font-semibold">{{ __('Days off') }}</h4>
                            <form method="POST" action="{{ route('scheduling.staff.days-off.store', $staff) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf
                                <input type="date" name="starts_on" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                <input type="date" name="ends_on" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                <input name="reason" placeholder="{{ __('Reason') }}" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950 sm:col-span-2">
                                <button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                                    {{ __('Add day off') }}
                                </button>
                            </form>

                            <div class="mt-3 space-y-2">
                                @forelse ($daysOff as $dayOff)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                        <span class="text-sm">{{ $dayOff->starts_on->format('Y-m-d') }} → {{ $dayOff->ends_on->format('Y-m-d') }}{{ $dayOff->reason ? ' · '.$dayOff->reason : '' }}</span>
                                        <form method="POST" action="{{ route('scheduling.staff.days-off.destroy', $dayOff) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-rose-600">{{ __('Remove') }}</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500">{{ __('No days off.') }}</p>
                                @endforelse
                            </div>
                        </div>

                        <div>
                            <h4 class="text-sm font-semibold">{{ __('Explicit availability') }}</h4>
                            <form method="POST" action="{{ route('scheduling.staff.availability.store', $staff) }}" class="mt-3 grid gap-2 sm:grid-cols-3">
                                @csrf
                                <input type="date" name="available_date" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                <input type="time" name="starts_at" value="09:00" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                <input type="time" name="ends_at" value="17:00" class="rounded-lg border-slate-300 bg-white dark:border-slate-700 dark:bg-slate-950">
                                <button class="sm:col-span-3 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                                    {{ __('Add availability') }}
                                </button>
                            </form>

                            <div class="mt-3 space-y-2">
                                @forelse ($availability as $item)
                                    <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950">
                                        <span class="text-sm">{{ $item->available_date->format('Y-m-d') }} · {{ substr($item->starts_at, 0, 5) }} → {{ substr($item->ends_at, 0, 5) }}</span>
                                        <form method="POST" action="{{ route('scheduling.staff.availability.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-sm text-rose-600">{{ __('Remove') }}</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500">{{ __('No explicit availability.') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Add staff members first to manage individual schedules.') }}</p>
            @endforelse
        </section>
    </div>
@endsection
