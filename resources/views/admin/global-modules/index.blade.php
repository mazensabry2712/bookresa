@extends('layouts.admin')

@section('title', __('Global Modules').' — BookResa')
@section('heading', __('Global Modules'))

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">{{ __('Platform catalog') }}</p>
        <h2 class="mt-1 text-2xl font-bold">{{ __('Global Modules') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('Control the module catalog globally. Workspace activation remains separate.') }}</p>
    </div>

    <section class="space-y-4">
        @foreach($modules as $module)
            <article class="br-panel p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="font-mono text-xs text-slate-400">{{ $module->key }}</p>
                        <h3 class="mt-1 text-lg font-bold">{{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ data_get($module->description, app()->getLocale()) }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold dark:bg-slate-800">{{ $module->is_core ? __('Core') : __('Optional') }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $module->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200' }}">{{ $module->is_active ? __('Globally active') : __('Globally disabled') }}</span>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200">{{ number_format($module->enabled_tenants_count) }} {{ __('active workspaces') }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.modules.update', $module) }}" class="mt-5 grid gap-4 md:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('English name') }}</span><input name="name_en" required maxlength="120" value="{{ data_get($module->name, 'en') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Arabic name') }}</span><input name="name_ar" required maxlength="120" value="{{ data_get($module->name, 'ar') }}" dir="rtl" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('English description') }}</span><textarea name="description_en" rows="3" maxlength="1000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-950">{{ data_get($module->description, 'en') }}</textarea></label>
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Arabic description') }}</span><textarea name="description_ar" rows="3" maxlength="1000" dir="rtl" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-950">{{ data_get($module->description, 'ar') }}</textarea></label>
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-800 md:col-span-2">
                        @if($module->is_core)
                            <input type="hidden" name="is_active" value="1">
                        @endif
                        <input type="checkbox" name="is_active" value="1" @checked($module->is_active) @disabled($module->is_core) class="h-4 w-4">
                        <span><span class="block font-semibold">{{ __('Globally active') }}</span><span class="text-xs text-slate-500">{{ $module->is_core ? __('Core modules are always active.') : __('Disabling here blocks the module for every workspace until re-enabled.') }}</span></span>
                    </label>
                    <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white md:col-span-2">{{ __('Save module') }}</button>
                </form>
            </article>
        @endforeach
    </section>
</div>
@endsection
