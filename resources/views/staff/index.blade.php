@extends('layouts.dashboard')

@section('title', __('app.staff_ui.staff').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.staff_ui.staff'))

@section('content')
    <x-onboarding-progress :current="4" />

    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $roleLabels = [
            'manager' => __('app.staff_ui.role_manager'),
            'receptionist' => __('app.staff_ui.role_receptionist'),
            'staff' => __('app.staff_ui.role_staff'),
        ];

        $statusLabels = [
            'active' => __('app.staff_ui.active'),
            'inactive' => __('app.staff_ui.inactive'),
        ];

        $createFormOld = old('_form') === 'staff-create';
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ __('app.staff_ui.workspace_operations') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.staff_ui.staff') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.staff_ui.page_help') }}</p>
            </div>

            @can('calendar.view')
                <a href="{{ route('scheduling.index', ['tenant' => $tenant->slug]) }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('app.staff_ui.configure_availability') }}
                </a>
            @endcan
        </section>

        @can('staff.manage')
            <details class="br-panel p-5 sm:p-6 group" @if (($staffMembers->total() === 0 && $search === '' && $statusFilter === '') || $createFormOld) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ __('app.staff_ui.new_member') }}</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">{{ __('app.staff_ui.add_staff_member') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.staff_ui.add_help') }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-indigo text-white" aria-hidden="true">
                        <svg class="h-5 w-5 transition-transform group-open:rotate-45" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                </summary>

                <form method="POST" action="{{ route('staff.store', ['tenant' => $tenant->slug]) }}" class="mt-5 space-y-5">
                    @csrf
                    <input type="hidden" name="_form" value="staff-create">

                    <div class="grid gap-4 lg:grid-cols-2">
                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.staff_ui.account_email') }}</span>
                            <input name="email" type="email" value="{{ $createFormOld ? old('email') : '' }}" required
                                   placeholder="{{ __('app.staff_ui.email_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                            <span class="block text-xs leading-5 text-slate-500">{{ __('app.staff_ui.account_email_help') }}</span>
                            @error('email')
                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.staff_ui.display_name') }}</span>
                            <input name="display_name" value="{{ $createFormOld ? old('display_name') : '' }}"
                                   placeholder="{{ __('app.staff_ui.display_name_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.staff_ui.phone') }}</span>
                            <input name="phone" type="tel" value="{{ $createFormOld ? old('phone') : '' }}" inputmode="tel"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.staff_ui.job_title') }}</span>
                            <input name="job_title" value="{{ $createFormOld ? old('job_title') : '' }}"
                                   placeholder="{{ __('app.staff_ui.job_title_placeholder') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 outline-none transition focus:border-brand-indigo focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.staff_ui.role') }}</span>
                            <select name="role" required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(($createFormOld ? old('role', 'staff') : 'staff') === $role)>
                                        {{ $roleLabels[$role] ?? str($role)->headline() }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold">{{ __('app.staff_ui.service_assignments') }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ __('app.staff_ui.service_assignments_help') }}</p>
                            </div>
                            @if ($services->isEmpty())
                                <span class="text-xs font-semibold text-amber-700 dark:text-amber-300">{{ __('app.staff_ui.no_services') }}</span>
                            @endif
                        </div>

                        @if ($services->isNotEmpty())
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($services as $service)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm transition hover:border-indigo-300 dark:border-slate-800 dark:hover:border-indigo-800">
                                        <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                               @checked(collect($createFormOld ? old('services', []) : [])->map(fn ($id) => (int) $id)->contains((int) $service->id))
                                               class="mt-0.5 rounded border-slate-300">
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-slate-900 dark:text-white">{{ $localized($service->name) }}</span>
                                            <span class="mt-0.5 block text-xs text-slate-500">
                                                {{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }}
                                                · {{ $service->duration_minutes }} {{ __('app.staff_ui.minutes') }}
                                                @unless ($service->is_active)
                                                    · {{ __('app.staff_ui.inactive') }}
                                                @endunless
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @if ($createFormOld)
                            @foreach ($errors->getMessages() as $field => $messages)
                                @if ($field === 'services' || str_starts_with($field, 'services.'))
                                    @foreach ($messages as $message)
                                        <p class="mt-2 text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</p>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ __('app.staff_ui.add_staff_member') }}
                        </button>
                    </div>
                </form>
            </details>
        @endcan

        <section class="br-panel overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                <div>
                    <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('app.staff_ui.staff_members') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ trans_choice('app.staff_ui.staff_count', $staffMembers->total(), ['count' => $staffMembers->total()]) }}</p>
                </div>
                <form method="GET" action="{{ route('staff.index', ['tenant' => $tenant->slug]) }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                    <label class="sr-only" for="staff-search">{{ __('app.staff_ui.search') }}</label>
                    <input id="staff-search" name="search" value="{{ $search }}" type="search"
                           placeholder="{{ __('app.staff_ui.search_placeholder') }}"
                           class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 sm:w-56">
                    <label>
                        <span class="sr-only">{{ __('app.staff_ui.status') }}</span>
                        <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 sm:w-36">
                            <option value="">{{ __('app.staff_ui.all_statuses') }}</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        {{ __('app.staff_ui.search') }}
                    </button>
                    @if ($search !== '' || $statusFilter !== '')
                        <a href="{{ route('staff.index', ['tenant' => $tenant->slug]) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800">
                            {{ __('app.staff_ui.reset') }}
                        </a>
                    @endif
                </form>
                @if ($staffMembers->hasPages())
                    <span class="text-xs font-semibold text-slate-400">{{ __('app.staff_ui.showing_page', ['page' => $staffMembers->currentPage()]) }}</span>
                @endif
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($staffMembers as $staffMember)
                    @php
                        $currentRole = $staffMember->user->getRoleNames()->first() ?? 'staff';
                        $status = $staffMember->status->value;
                        $staffFormKey = 'staff-update-'.$staffMember->id;
                        $staffFormOld = old('_form') === $staffFormKey;
                        $assignedServiceIds = $staffMember->services->pluck('id')->map(fn ($id) => (int) $id)->all();
                    @endphp

                    <article class="p-5 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex min-w-0 items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-base font-extrabold text-brand-navy dark:bg-slate-800 dark:text-indigo-300">
                                    {{ str($staffMember->display_name)->substr(0, 1)->upper() }}
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="truncate text-base font-bold text-slate-950 dark:text-white">{{ $staffMember->display_name }}</h4>
                                        <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300">
                                            {{ $roleLabels[$currentRole] ?? str($currentRole)->headline() }}
                                        </span>
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $status === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                            {{ $statusLabels[$status] ?? str($status)->headline() }}
                                        </span>
                                    </div>

                                    <p class="mt-1 truncate text-sm text-slate-500">{{ $staffMember->user->email }}</p>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                                        @if ($staffMember->phone)
                                            <span>{{ $staffMember->phone }}</span>
                                        @endif
                                        @if ($staffMember->job_title)
                                            <span>{{ $staffMember->job_title }}</span>
                                        @endif
                                        <span>
                                            {{ trans_choice('app.staff_ui.assigned_service_count', $staffMember->services->count(), ['count' => $staffMember->services->count()]) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </div>

                        @can('staff.manage')
                            <details class="group mt-4 border-t border-slate-200 pt-4 dark:border-slate-800" @if ($staffFormOld && $errors->any()) open @endif>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-brand-indigo hover:text-indigo-700 [&::-webkit-details-marker]:hidden">
                                    <span>{{ __('app.staff_ui.edit_below') }}</span>
                                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                    </svg>
                                </summary>

                            <form method="POST" action="{{ route('staff.update', ['tenant' => $tenant->slug, 'staff' => $staffMember]) }}" class="mt-5 space-y-5">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_form" value="{{ $staffFormKey }}">

                                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                    <label class="space-y-1.5 text-sm">
                                        <span class="font-semibold">{{ __('app.staff_ui.display_name') }}</span>
                                        <input name="display_name" type="text" value="{{ $staffFormOld ? old('display_name', $staffMember->display_name) : $staffMember->display_name }}" required
                                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                        @if ($staffFormOld)
                                            @error('display_name')
                                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </label>

                                    <label class="space-y-1.5 text-sm">
                                        <span class="font-semibold">{{ __('app.staff_ui.phone') }}</span>
                                        <input name="phone" type="tel" value="{{ $staffFormOld ? old('phone', $staffMember->phone) : $staffMember->phone }}"
                                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                        @if ($staffFormOld)
                                            @error('phone')
                                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </label>

                                    <label class="space-y-1.5 text-sm">
                                        <span class="font-semibold">{{ __('app.staff_ui.job_title') }}</span>
                                        <input name="job_title" value="{{ $staffFormOld ? old('job_title', $staffMember->job_title) : $staffMember->job_title }}"
                                               class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                        @if ($staffFormOld)
                                            @error('job_title')
                                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </label>

                                    <label class="space-y-1.5 text-sm">
                                        <span class="font-semibold">{{ __('app.staff_ui.role') }}</span>
                                        <select name="role" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role }}" @selected(($staffFormOld ? old('role', $currentRole) : $currentRole) === $role)>{{ $roleLabels[$role] ?? str($role)->headline() }}</option>
                                            @endforeach
                                        </select>
                                        @if ($staffFormOld)
                                            @error('role')
                                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </label>
                                </div>

                                <div class="grid gap-4 lg:grid-cols-[0.8fr_2fr]">
                                    <label class="space-y-1.5 text-sm">
                                        <span class="font-semibold">{{ __('app.staff_ui.status') }}</span>
                                        <select name="status" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                            @foreach ($statusLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($staffFormOld ? old('status', $status) : $status) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @if ($staffFormOld)
                                            @error('status')
                                                <span class="block text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </label>

                                    <div>
                                        <p class="text-sm font-semibold">{{ __('app.staff_ui.assigned_services') }}</p>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($services as $service)
                                                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-800">
                                                    <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                                           @checked(collect($staffFormOld ? old('services', $assignedServiceIds) : $assignedServiceIds)->map(fn ($id) => (int) $id)->contains((int) $service->id))
                                                           class="mt-0.5 rounded border-slate-300">
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-semibold">{{ $localized($service->name) }}</span>
                                                        <span class="mt-0.5 block text-xs text-slate-500">{{ $service->is_active ? __('app.staff_ui.active') : __('app.staff_ui.inactive') }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs leading-5 text-slate-500">{{ __('app.staff_ui.availability_help') }}</p>
                                    @can('calendar.view')
                                        <a href="{{ route('scheduling.index', ['tenant' => $tenant->slug]) }}" class="text-sm font-bold text-brand-indigo hover:underline">
                                            {{ __('app.staff_ui.configure_availability') }}
                                        </a>
                                    @endcan
                                    <button type="submit"
                                            class="inline-flex min-h-10 items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                                        {{ __('app.staff_ui.save_changes') }}
                                    </button>
                                </div>
                                @if ($staffFormOld)
                                    @foreach ($errors->getMessages() as $field => $messages)
                                        @if ($field === 'services' || str_starts_with($field, 'services.'))
                                            @foreach ($messages as $message)
                                                <p class="mt-2 text-xs font-semibold text-rose-700 dark:text-rose-300">{{ $message }}</p>
                                            @endforeach
                                        @endif
                                    @endforeach
                                @endif
                            </form>
                            </details>
                        @else
                            <p class="mt-5 border-t border-slate-200 pt-4 text-sm leading-6 text-slate-500 dark:border-slate-800">{{ __('app.staff_ui.view_only') }}</p>
                        @endcan
                    </article>
                @empty
                    <div class="px-5 py-12">
                        <div class="mx-auto max-w-md text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="3"/>
                                    <path stroke-linecap="round" d="M5.5 20a6.5 6.5 0 0 1 13 0"/>
                                </svg>
                            </div>
                            <h4 class="mt-4 font-semibold text-slate-950 dark:text-white">{{ __('app.staff_ui.no_staff') }}</h4>
                            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.staff_ui.no_staff_help') }}</p>
                        </div>
                    </div>
                @endforelse
            </div>

            @if ($staffMembers->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">
                    {{ $staffMembers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection