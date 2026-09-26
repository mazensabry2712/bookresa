@extends('layouts.dashboard')

@section('title', __('Staff'))
@section('heading', __('Staff'))

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5">
                <h2 class="text-base font-semibold">{{ __('Add staff member') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('The user must already have a BookResa account. You can assign their role and services here.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('staff.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium">{{ __('Account email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none ring-offset-2 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-slate-800">
                </div>

                <div>
                    <label for="display_name" class="mb-1.5 block text-sm font-medium">{{ __('Display name') }}</label>
                    <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none ring-offset-2 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-medium">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none ring-offset-2 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label for="job_title" class="mb-1.5 block text-sm font-medium">{{ __('Job title') }}</label>
                    <input id="job_title" name="job_title" type="text" value="{{ old('job_title') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none ring-offset-2 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label for="role" class="mb-1.5 block text-sm font-medium">{{ __('Role') }}</label>
                    <select id="role" name="role" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none ring-offset-2 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', 'staff') === $role)>
                                {{ IlluminateSupportStr::headline($role) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">{{ __('Services') }}</label>
                    <div class="max-h-44 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                        @forelse ($services as $service)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                       @checked(in_array($service->id, old('services', []), true))
                                       class="mt-0.5 rounded border-slate-300">
                                <span>
                                    <span class="block font-medium">{{ $service->name[app()->getLocale()] ?? $service->name['en'] ?? '—' }}</span>
                                    <span class="text-xs text-slate-500">
                                        {{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }}
                                        · {{ $service->duration_minutes }} {{ __('min') }}
                                        @unless ($service->is_active)
                                            · {{ __('Inactive') }}
                                        @endunless
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('Create services first to assign them to staff.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                        {{ __('Add staff member') }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold">{{ __('Staff members') }}</h2>
            </div>

            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($staffMembers as $staffMember)
                    @php
                        $currentRole = $staffMember->user->getRoleNames()->first() ?? 'staff';
                    @endphp

                    <form method="POST" action="{{ route('staff.update', $staffMember) }}" class="space-y-5 p-5">
                        @csrf
                        @method('PUT')

                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-base font-semibold">{{ $staffMember->display_name }}</p>
                                <p class="text-sm text-slate-500">{{ $staffMember->user->email }}</p>
                                @if ($staffMember->job_title)
                                    <p class="mt-1 text-xs text-slate-500">{{ $staffMember->job_title }}</p>
                                @endif
                            </div>

                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $staffMember->status->value === 'active'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                    : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ IlluminateSupportStr::headline($staffMember->status->value) }}
                            </span>
                        </div>

                        @can('staff.manage')
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium">{{ __('Display name') }}</label>
                                    <input name="display_name" type="text" value="{{ $staffMember->display_name }}" required
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium">{{ __('Phone') }}</label>
                                    <input name="phone" type="text" value="{{ $staffMember->phone }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium">{{ __('Job title') }}</label>
                                    <input name="job_title" type="text" value="{{ $staffMember->job_title }}"
                                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium">{{ __('Role') }}</label>
                                    <select name="role" required
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" @selected($currentRole === $role)>
                                                {{ IlluminateSupportStr::headline($role) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium">{{ __('Status') }}</label>
                                <select name="status" required
                                        class="w-full max-w-xs rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <option value="active" @selected($staffMember->status->value === 'active')>{{ __('Active') }}</option>
                                    <option value="inactive" @selected($staffMember->status->value === 'inactive')>{{ __('Inactive') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium">{{ __('Assigned services') }}</label>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($services as $service)
                                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm dark:border-slate-700">
                                            <input type="checkbox" name="services[]" value="{{ $service->id }}"
                                                   @checked($staffMember->services->contains('id', $service->id))
                                                   class="mt-0.5 rounded border-slate-300">
                                            <span>
                                                <span class="block font-medium">{{ $service->name[app()->getLocale()] ?? $service->name['en'] ?? '—' }}</span>
                                                <span class="text-xs text-slate-500">{{ $service->is_active ? __('Active') : __('Inactive') }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <button type="submit"
                                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                                {{ __('Save staff changes') }}
                            </button>
                        @else
                            <div class="text-sm text-slate-500">
                                {{ __('You have view-only access to staff members.') }}
                            </div>
                        @endcan
                    </form>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm text-slate-500">{{ __('No staff members yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
