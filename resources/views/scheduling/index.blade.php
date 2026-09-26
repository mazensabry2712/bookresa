@extends('layouts.dashboard')

@section('title', __('Scheduling').' — '.config('app.name', 'BookResa'))
@section('heading', __('Scheduling'))

@section('content')
    @php
        $days = [
            \App\Domain\Scheduling\Enums\DayOfWeek::Monday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Tuesday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Wednesday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Thursday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Friday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Saturday,
            \App\Domain\Scheduling\Enums\DayOfWeek::Sunday,
        ];
    @endphp

    <div class="space-y-6">
        <section>
            <p class="text-sm text-slate-500">{{ __('Workspace operations') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Scheduling') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Control business hours and staff availability without changing the booking engine.') }}</p>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="mb-5">
                <h3 class="font-semibold">{{ __('Business working hours') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('These hours are the base schedule used by availability calculations.') }}</p>
            </div>

            @can('settings.manage')
                <form method="POST" action="{{ route('scheduling.business-hours.update') }}" class="space-y-3">
                    @csrf
                    @method('PUT')

                    @foreach ($days as $day)
                        @php
                            $hour = $businessHours->get($day->value);
                            $open = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                            $close = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                        @endphp
                        <div class="grid items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800 sm:grid-cols-[9rem_1fr_1fr_auto]">
                            <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">
                            <div class="text-sm font-semibold">{{ __($day->name) }}</div>
                            <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}"
                                   class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}"
                                   class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($hour?->is_closed ?? true)>
                                <span>{{ __('Closed') }}</span>
                            </label>
                        </div>
                    @endforeach

                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                        {{ __('Save business hours') }}
                    </button>
                </form>
            @else
                <div class="space-y-2">
                    @foreach ($days as $day)
                        @php $hour = $businessHours->get($day->value); @endphp
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-800">
                            <span class="font-medium">{{ __($day->name) }}</span>
                            <span class="text-slate-500">
                                {{ $hour?->is_closed ? __('Closed') : (($hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '—').' — '.($hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '—')) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endcan
        </section>

        <div class="grid gap-6 xl:grid-cols-3">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="mb-4">
                    <h3 class="font-semibold">{{ __('Breaks') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Recurring breaks removed from daily availability.') }}</p>
                </div>

                @can('settings.manage')
                    <form method="POST" action="{{ route('scheduling.breaks.store') }}" class="space-y-3">
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
                        <input name="label" placeholder="{{ __('Label (optional)') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Add break') }}</button>
                    </form>
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($businessBreaks as $break)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                            <div>
                                <p class="font-medium">{{ __($days[$break->day_of_week - 1]->name) }}</p>
                                <p class="text-slate-500">{{ substr((string) $break->starts_at, 0, 5) }} — {{ substr((string) $break->ends_at, 0, 5) }}{{ $break->label ? ' · '.$break->label : '' }}</p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.breaks.destroy', $break) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-700 dark:text-rose-300">{{ __('Remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No breaks configured.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="mb-4">
                    <h3 class="font-semibold">{{ __('Holidays') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Full business closure dates.') }}</p>
                </div>

                @can('settings.manage')
                    <form method="POST" action="{{ route('scheduling.holidays.store') }}" class="space-y-3">
                        @csrf
                        <input type="date" name="holiday_date" required value="{{ old('holiday_date', now()->toDateString()) }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <input name="reason" placeholder="{{ __('Reason (optional)') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save holiday') }}</button>
                    </form>
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($businessHolidays as $holiday)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                            <div>
                                <p class="font-medium">{{ $holiday->holiday_date->format('Y-m-d') }}</p>
                                <p class="text-slate-500">{{ $holiday->reason ?: __('Business closed') }}</p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.holidays.destroy', $holiday) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-700 dark:text-rose-300">{{ __('Remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No upcoming holidays configured.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                <div class="mb-4">
                    <h3 class="font-semibold">{{ __('Special working hours') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Date-specific hours override the regular schedule.') }}</p>
                </div>

                @can('settings.manage')
                    <form method="POST" action="{{ route('scheduling.special-hours.store') }}" class="space-y-3">
                        @csrf
                        <input type="date" name="work_date" required value="{{ old('work_date', now()->toDateString()) }}"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <div class="grid grid-cols-2 gap-3">
                            <input type="time" name="opens_at" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <input type="time" name="closes_at" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <input name="reason" placeholder="{{ __('Reason (optional)') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_closed" value="1">
                            <span>{{ __('Closed all day') }}</span>
                        </label>
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save special hours') }}</button>
                    </form>
                @endcan

                <div class="mt-5 space-y-2">
                    @forelse ($specialWorkingHours as $special)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                            <div>
                                <p class="font-medium">{{ $special->work_date->format('Y-m-d') }}</p>
                                <p class="text-slate-500">
                                    {{ $special->is_closed ? __('Closed') : (substr((string) $special->opens_at, 0, 5).' — '.substr((string) $special->closes_at, 0, 5)) }}
                                    {{ $special->reason ? ' · '.$special->reason : '' }}
                                </p>
                            </div>
                            @can('settings.manage')
                                <form method="POST" action="{{ route('scheduling.special-hours.destroy', $special) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-700 dark:text-rose-300">{{ __('Remove') }}</button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No upcoming special hours configured.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="font-semibold">{{ __('Staff scheduling') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Set recurring staff hours plus dates when a staff member is explicitly available or unavailable.') }}</p>
                </div>
                @if ($staffMembers->isNotEmpty())
                    <form method="GET" action="{{ route('scheduling.index') }}" class="min-w-56">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Staff member') }}</label>
                        <select name="staff" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($staffMembers as $staffMember)
                                <option value="{{ $staffMember->id }}" @selected($selectedStaff?->id === $staffMember->id)>
                                    {{ $staffMember->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if ($selectedStaff)
                @php
                    $staffHours = $selectedStaffHours;
                    $staffDaysOff = $selectedStaffDaysOff;
                    $staffAvailability = $selectedStaffAvailability;
                @endphp

                <div class="mt-5 flex flex-col gap-1">
                    <p class="font-semibold">{{ $selectedStaff->display_name }}</p>
                    <p class="text-sm text-slate-500">{{ $selectedStaff->user?->email }}</p>
                </div>

                @can('settings.manage')
                    <form method="POST" action="{{ route('scheduling.staff-hours.update', $selectedStaff) }}" class="mt-5 space-y-3">
                        @csrf
                        @method('PUT')

                        @foreach ($days as $day)
                            @php
                                $hour = $staffHours->get($day->value);
                                $open = $hour?->opens_at ? substr((string) $hour->opens_at, 0, 5) : '';
                                $close = $hour?->closes_at ? substr((string) $hour->closes_at, 0, 5) : '';
                            @endphp
                            <div class="grid items-center gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800 sm:grid-cols-[9rem_1fr_1fr_auto]">
                                <input type="hidden" name="hours[{{ $loop->index }}][day_of_week]" value="{{ $day->value }}">
                                <div class="text-sm font-semibold">{{ __($day->name) }}</div>
                                <input type="time" name="hours[{{ $loop->index }}][opens_at]" value="{{ $open }}"
                                       class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <input type="time" name="hours[{{ $loop->index }}][closes_at]" value="{{ $close }}"
                                       class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="hours[{{ $loop->index }}][is_closed]" value="1" @checked($hour?->is_closed ?? true)>
                                    <span>{{ __('Closed') }}</span>
                                </label>
                            </div>
                        @endforeach

                        <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save staff hours') }}</button>
                    </form>
                @endcan

                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div>
                        <div class="mb-4 flex items-end justify-between gap-3">
                            <div>
                                <h4 class="font-semibold">{{ __('Days off') }}</h4>
                                <p class="mt-1 text-sm text-slate-500">{{ __('A date range where this staff member cannot be booked.') }}</p>
                            </div>
                        </div>

                        @can('settings.manage')
                            <form method="POST" action="{{ route('scheduling.staff-days-off.store', $selectedStaff) }}" class="space-y-3">
                                @csrf
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="date" name="starts_on" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <input type="date" name="ends_on" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </div>
                                <input name="reason" placeholder="{{ __('Reason (optional)') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <button class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Add day off') }}</button>
                            </form>
                        @endcan

                        <div class="mt-4 space-y-2">
                            @forelse ($staffDaysOff as $dayOff)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                                    <div>
                                        <p class="font-medium">{{ $dayOff->starts_on->format('Y-m-d') }} — {{ $dayOff->ends_on->format('Y-m-d') }}</p>
                                        <p class="text-slate-500">{{ $dayOff->reason ?: __('Time off') }}</p>
                                    </div>
                                    @can('settings.manage')
                                        <form method="POST" action="{{ route('scheduling.staff-days-off.destroy', [$selectedStaff, $dayOff]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-rose-700 dark:text-rose-300">{{ __('Remove') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">{{ __('No days off configured.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ __('Explicit availability') }}</h4>
                            <p class="mt-1 text-sm text-slate-500">{{ __('Add a specific date/time window for this staff member.') }}</p>
                        </div>

                        @can('settings.manage')
                            <form method="POST" action="{{ route('scheduling.staff-availability.store', $selectedStaff) }}" class="space-y-3">
                                @csrf
                                <input type="date" name="available_date" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="time" name="starts_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <input type="time" name="ends_at" required class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </div>
                                <button class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Add availability') }}</button>
                            </form>
                        @endcan

                        <div class="mt-4 space-y-2">
                            @forelse ($staffAvailability as $availability)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                                    <div>
                                        <p class="font-medium">{{ $availability->available_date->format('Y-m-d') }}</p>
                                        <p class="text-slate-500">{{ substr((string) $availability->starts_at, 0, 5) }} — {{ substr((string) $availability->ends_at, 0, 5) }}</p>
                                    </div>
                                    @can('settings.manage')
                                        <form method="POST" action="{{ route('scheduling.staff-availability.destroy', [$selectedStaff, $availability]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-semibold text-rose-700 dark:text-rose-300">{{ __('Remove') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">{{ __('No explicit availability configured.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-6 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700">
                    {{ __('Add staff members first to configure staff schedules.') }}
                </div>
            @endif
        </section>
    </div>
@endsection
