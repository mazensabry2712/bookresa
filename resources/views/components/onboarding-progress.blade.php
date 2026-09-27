@props(['current'])

@php
    $steps = [
        ['key' => 'business', 'label' => __('app.create_business')],
        ['key' => 'services', 'label' => __('app.onboarding_steps.services')],
        ['key' => 'hours', 'label' => __('app.onboarding_steps.hours')],
        ['key' => 'staff', 'label' => __('app.onboarding_steps.staff')],
    ];

    $isOnboarding = ! (bool) data_get($tenant->settings ?? [], 'onboarding.completed', false);
@endphp

@if ($isOnboarding)
    <div class="mb-6 br-panel px-4 py-4 sm:px-5" aria-label="{{ __('app.setup_progress') }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">
                    {{ __('app.step_progress', ['current' => $current, 'total' => 4]) }}
                </p>
                <p class="mt-1 truncate text-sm font-semibold text-slate-950 dark:text-white">
                    {{ $steps[$current - 1]['label'] }}
                </p>
            </div>

            <div class="flex min-w-0 flex-1 gap-1.5 sm:max-w-md" aria-hidden="true">
                @foreach ($steps as $index => $step)
                    <span class="h-1.5 flex-1 rounded-full {{ $index + 1 <= $current ? 'bg-brand-indigo' : 'bg-slate-200 dark:bg-slate-800' }}"></span>
                @endforeach
            </div>
        </div>
    </div>
@endif
