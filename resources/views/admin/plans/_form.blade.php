<div class="grid gap-5 lg:grid-cols-2">
    <div><label class="text-sm font-semibold">{{ __('Name (English)') }}</label><input name="name[en]" value="{{ old('name.en', data_get($plan?->name, 'en')) }}" required maxlength="120" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Name (Arabic)') }}</label><input name="name[ar]" value="{{ old('name.ar', data_get($plan?->name, 'ar')) }}" maxlength="120" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Description (English)') }}</label><textarea name="description[en]" rows="3" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old('description.en', data_get($plan?->description, 'en')) }}</textarea></div>
    <div><label class="text-sm font-semibold">{{ __('Description (Arabic)') }}</label><textarea name="description[ar]" rows="3" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">{{ old('description.ar', data_get($plan?->description, 'ar')) }}</textarea></div>
    <div><label class="text-sm font-semibold">{{ __('Price') }}</label><input name="price" value="{{ old('price', $plan ? number_format($plan->price_minor / 100, 2, '.', '') : '0.00') }}" inputmode="decimal" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Currency') }}</label><input name="currency" value="{{ old('currency', $plan?->currency ?? 'EGP') }}" maxlength="3" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm uppercase dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Billing period') }}</label><select name="billing_period" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">@foreach (\App\Domain\Billing\Enums\PlanBillingPeriod::cases() as $period)<option value="{{ $period->value }}" @selected(old('billing_period', $plan?->billing_period->value ?? 'monthly') === $period->value)>{{ str($period->value)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
    <div><label class="text-sm font-semibold">{{ __('Trial days') }}</label><input name="trial_days" type="number" min="0" max="3650" value="{{ old('trial_days', $plan?->trial_days ?? 0) }}" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Included customers') }}</label><input name="included_customer_limit" type="number" min="0" value="{{ old('included_customer_limit', $plan?->included_customer_limit ?? 0) }}" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
    <div><label class="text-sm font-semibold">{{ __('Additional customer price') }}</label><input name="additional_customer_price" value="{{ old('additional_customer_price', $plan ? number_format($plan->additional_customer_price_minor / 100, 2, '.', '') : '0.00') }}" inputmode="decimal" required class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></div>
</div>
<div class="mt-6">
    <p class="text-sm font-semibold">{{ __('Enabled modules') }}</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-2">
        @php $selectedModules = array_map('intval', old('module_ids', $plan?->modules?->pluck('id')->all() ?? [])); @endphp
        @foreach ($modules as $module)
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                <input type="checkbox" name="module_ids[]" value="{{ $module->id }}" @checked(in_array($module->id, $selectedModules, true)) class="mt-1 rounded border-slate-300">
                <span><span class="block text-sm font-semibold">{{ data_get($module->name, app()->getLocale()) ?? data_get($module->name, 'en') ?? $module->key }}</span><span class="mt-1 block text-xs text-slate-500">{{ $module->key }}</span></span>
            </label>
        @endforeach
    </div>
</div>
<div class="mt-6"><label class="inline-flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan?->is_active ?? true)) class="rounded border-slate-300"> {{ __('Plan is active') }}</label></div>
<div class="mt-8 flex flex-wrap gap-3">
    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ $submitLabel }}</button>
    <a href="{{ route('admin.plans.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Cancel') }}</a>
</div>
