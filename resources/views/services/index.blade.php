@extends('layouts.dashboard')

@section('title', __('Services').' — '.config('app.name', 'BookResa'))
@section('heading', __('Services'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ __('Workspace operations') }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Services') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Manage bookable services, pricing and duration.') }}</p>
            </div>
            <a href="{{ route('services.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('New service') }}</a>
        </div>

        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <h3 class="font-semibold">{{ $editingService ? __('Edit service') : __('Create service') }}</h3>
            <form method="POST" action="{{ $editingService ? route('services.update', $editingService) : route('services.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                @if ($editingService)
                    @method('PUT')
                @endif

                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Name (English)') }}</span>
                    <input name="name_en" value="{{ old('name_en', data_get($editingService?->name, 'en')) }}" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Name (Arabic)') }}</span>
                    <input name="name_ar" value="{{ old('name_ar', data_get($editingService?->name, 'ar')) }}" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="space-y-1 text-sm sm:col-span-2">
                    <span class="font-medium">{{ __('Description (English)') }}</span>
                    <textarea name="description_en" rows="2" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('description_en', data_get($editingService?->description, 'en')) }}</textarea>
                </label>
                <label class="space-y-1 text-sm sm:col-span-2">
                    <span class="font-medium">{{ __('Description (Arabic)') }}</span>
                    <textarea name="description_ar" rows="2" class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">{{ old('description_ar', data_get($editingService?->description, 'ar')) }}</textarea>
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Price') }}</span>
                    <input name="price" value="{{ old('price', $editingService ? number_format($editingService->price_minor / 100, 2, '.', '') : '0.00') }}" inputmode="decimal" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Currency') }}</span>
                    <input name="currency" value="{{ old('currency', $editingService?->currency ?? 'EGP') }}" maxlength="3" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 uppercase dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Duration (minutes)') }}</span>
                    <input type="number" min="1" max="1440" name="duration_minutes" value="{{ old('duration_minutes', $editingService?->duration_minutes ?? 30) }}" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-medium">{{ __('Buffer (minutes)') }}</span>
                    <input type="number" min="0" max="1440" name="buffer_minutes" value="{{ old('buffer_minutes', $editingService?->buffer_minutes ?? 0) }}" required class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingService?->is_active ?? true))>
                    <span>{{ __('Active service') }}</span>
                </label>
                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ $editingService ? __('Update service') : __('Create service') }}</button>
                    @if ($editingService)
                        <a href="{{ route('services.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Cancel') }}</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                <h3 class="font-semibold">{{ __('Service list') }}</h3>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($services as $service)
                    <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ data_get($service->name, app()->getLocale()) ?? data_get($service->name, 'en') }}</p>
                                <span class="rounded-full px-2 py-1 text-xs {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $service->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }}
                                · {{ $service->duration_minutes }} {{ __('min') }}
                                @if ($service->buffer_minutes > 0)
                                    · {{ $service->buffer_minutes }} {{ __('buffer') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @can('services.update')
                                <a href="{{ route('services.index', ['edit' => $service->id]) }}" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold dark:border-slate-700">{{ __('Edit') }}</a>
                            @endcan
                            @can('services.delete')
                                <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('{{ __('Delete this service?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:text-rose-300">{{ __('Delete') }}</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-sm text-slate-500">{{ __('No services yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
    @if ($services->hasPages())
        <div class="mt-4">
            {{ $services->links() }}
        </div>
    @endif
@endsection
