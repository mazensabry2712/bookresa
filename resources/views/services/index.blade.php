@extends('layouts.dashboard')

@section('title', __('service_ui.services').' — '.config('app.name', 'BookResa'))
@section('heading', __('service_ui.services'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $isEditing = $editingService !== null;
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ __('service_ui.workspace_operations') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('service_ui.services') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('service_ui.page_help') }}</p>
            </div>

            @if ($isEditing)
                <a href="{{ route('services.index') }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    {{ __('service_ui.create_new') }}
                </a>
            @else
                <a href="#service-form"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                    {{ __('service_ui.new_service') }}
                </a>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)]">
            <section id="service-form" class="br-panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                            {{ $isEditing ? __('service_ui.editing') : __('service_ui.new_service') }}
                        </p>
                        <h3 class="mt-2 text-xl font-bold text-slate-950 dark:text-white">
                            {{ $isEditing ? __('service_ui.edit_service') : __('service_ui.create_service') }}
                        </h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('service_ui.form_help') }}</p>
                    </div>

                    @if ($isEditing)
                        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300">
                            #{{ $editingService->id }}
                        </span>
                    @endif
                </div>

                <form method="POST"
                      action="{{ $isEditing ? route('services.update', $editingService) : route('services.store') }}"
                      class="mt-5 space-y-5">
                    @csrf

                    @if ($isEditing)
                        @method('PUT')
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.name_english') }}</span>
                            <input name="name_en"
                                   value="{{ old('name_en', data_get($editingService?->name, 'en')) }}"
                                   required maxlength="160"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.name_arabic') }}</span>
                            <input name="name_ar"
                                   value="{{ old('name_ar', data_get($editingService?->name, 'ar')) }}"
                                   maxlength="160" dir="rtl"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="space-y-1.5 text-sm sm:col-span-2">
                            <span class="font-semibold">{{ __('service_ui.description_english') }}</span>
                            <textarea name="description_en" rows="2" maxlength="5000"
                                      class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('description_en', data_get($editingService?->description, 'en')) }}</textarea>
                        </label>

                        <label class="space-y-1.5 text-sm sm:col-span-2">
                            <span class="font-semibold">{{ __('service_ui.description_arabic') }}</span>
                            <textarea name="description_ar" rows="2" maxlength="5000" dir="rtl"
                                      class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">{{ old('description_ar', data_get($editingService?->description, 'ar')) }}</textarea>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.price') }}</span>
                            <input name="price"
                                   value="{{ old('price', $isEditing ? number_format($editingService->price_minor / 100, 2, '.', '') : '0.00') }}"
                                   inputmode="decimal" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.currency') }}</span>
                            <input name="currency"
                                   value="{{ old('currency', $editingService?->currency ?? 'EGP') }}"
                                   maxlength="3" minlength="3" required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 uppercase dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.duration') }}</span>
                            <input type="number" min="1" max="1440" name="duration_minutes"
                                   value="{{ old('duration_minutes', $editingService?->duration_minutes ?? 30) }}"
                                   required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('service_ui.buffer') }}</span>
                            <input type="number" min="0" max="1440" name="buffer_minutes"
                                   value="{{ old('buffer_minutes', $editingService?->buffer_minutes ?? 0) }}"
                                   required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>
                    </div>

                    <label class="flex items-start gap-3 rounded-xl br-surface-soft p-4 text-sm">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $editingService?->is_active ?? true))
                               class="mt-0.5 rounded border-slate-300">
                        <span>
                            <span class="block font-semibold">{{ __('service_ui.active_service') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-slate-500">{{ __('service_ui.active_service_help') }}</span>
                        </span>
                    </label>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="submit"
                                class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-600">
                            {{ $isEditing ? __('service_ui.update_service') : __('service_ui.create_service') }}
                        </button>

                        @if ($isEditing)
                            <a href="{{ route('services.index') }}"
                               class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ __('app.cancel') }}
                            </a>
                        @endif
                    </div>
                </form>
            </section>

            <section class="br-panel overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div>
                        <h3 class="font-semibold text-slate-950 dark:text-white">{{ __('service_ui.service_list') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ trans_choice('service_ui.service_count', $services->total(), ['count' => $services->total()]) }}</p>
                    </div>
                    @if ($services->hasPages())
                        <span class="text-xs font-semibold text-slate-400">{{ __('service_ui.showing_page', ['page' => $services->currentPage()]) }}</span>
                    @endif
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($services as $service)
                        @php
                            $serviceName = $localized($service->name);
                            $serviceDescription = $localized($service->description);
                        @endphp

                        <article class="px-5 py-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/30">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="truncate font-semibold text-slate-950 dark:text-white">{{ $serviceName }}</h4>
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold {{ $service->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                            {{ $service->is_active ? __('service_ui.active') : __('service_ui.inactive') }}
                                        </span>
                                    </div>

                                    @if ($serviceDescription !== '—')
                                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ $serviceDescription }}</p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                        <span class="font-bold text-slate-950 dark:text-white">{{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }}</span>
                                        <span>{{ $service->duration_minutes }} {{ __('service_ui.minutes') }}</span>
                                        @if ($service->buffer_minutes > 0)
                                            <span>{{ $service->buffer_minutes }} {{ __('service_ui.buffer_short') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    @can('services.update')
                                        <a href="{{ route('services.index', ['edit' => $service->id]) }}"
                                           class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 px-3.5 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                            {{ __('service_ui.edit') }}
                                        </a>
                                    @endcan

                                    @can('services.delete')
                                        <form method="POST"
                                              action="{{ route('services.destroy', $service) }}"
                                              onsubmit="return confirm(@json(__('service_ui.delete_confirm', ['name' => $serviceName])))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-rose-200 px-3.5 py-2 text-sm font-bold text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30">
                                                {{ __('service_ui.delete') }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-12">
                            <div class="mx-auto max-w-md text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" d="M6 6h12M6 10h12M6 14h8M6 18h6"/>
                                    </svg>
                                </div>
                                <h4 class="mt-4 font-semibold text-slate-950 dark:text-white">{{ __('service_ui.no_services') }}</h4>
                                <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('service_ui.no_services_help') }}</p>
                                <a href="#service-form"
                                   class="mt-5 inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-indigo px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-600">
                                    {{ __('service_ui.create_service') }}
                                </a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        @if ($services->hasPages())
            <div>
                {{ $services->links() }}
            </div>
        @endif
    </div>
@endsection
