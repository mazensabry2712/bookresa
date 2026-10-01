@extends('layouts.admin')

@section('title', __('app.workspace_overview').' — BookResa')
@section('heading', __('app.workspace_overview'))

@section('content')
    @php
        $businessName = data_get($tenant->profile?->name, app()->getLocale())
            ?? data_get($tenant->profile?->name, 'en')
            ?? $tenant->slug;
        $businessType = data_get($tenant->businessType?->name, app()->getLocale())
            ?? data_get($tenant->businessType?->name, 'en')
            ?? $tenant->businessType?->slug
            ?? '—';
        $ownerUser = $owner?->user;
        $subscriptionPlan = data_get($latestSubscription?->plan?->name, app()->getLocale())
            ?? data_get($latestSubscription?->plan?->name, 'en')
            ?? $latestSubscription?->plan?->slug
            ?? __('app.no_active_subscription');
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm text-slate-500">{{ __('app.super_admin') }} · {{ __('app.businesses') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight">{{ $businessName }}</h2>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tenant->status === \App\Domain\Tenant\Enums\TenantStatus::Active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200' }}">
                        {{ $tenant->status->value === 'active' ? __('app.active') : __('app.suspended') }}
                    </span>
                </div>
                <p class="mt-1 break-all text-sm text-slate-500">{{ $tenant->slug }} · {{ $businessType }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard', ['tenant' => $tenant->slug]) }}" class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">
                    {{ __('app.open_workspace') }}
                </a>
                <a href="{{ route('admin.businesses.edit', $tenant) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                    {{ __('platform.edit_workspace') }}
                </a>
                <a href="{{ route('admin.customers.index', ['tenant_id' => $tenant->id]) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                    {{ __('platform.customer_intelligence') }}
                </a>
                <a href="{{ route('admin.businesses.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                    {{ __('app.back_to_businesses') }}
                </a>
                <form method="POST" action="{{ route('admin.businesses.toggle-status', $tenant) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">
                        {{ $tenant->status === \App\Domain\Tenant\Enums\TenantStatus::Suspended ? __('Activate') : __('Suspend') }}
                    </button>
                </form>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([
                ['label' => __('app.customers'), 'value' => $stats['customers']],
                ['label' => __('app.bookings'), 'value' => $stats['bookings']],
                ['label' => __('app.services'), 'value' => $stats['services']],
                ['label' => __('app.staff'), 'value' => $stats['staff']],
                ['label' => __('app.active_members'), 'value' => $stats['activeMembers']],
                ['label' => __('app.paid_revenue'), 'value' => number_format($stats['paidRevenueMinor'] / 100, 2).' '.($latestSubscription?->currency ?? 'EGP')],
            ] as $stat)
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold">{{ __('app.workspace_subscription') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.workspace_subscription_help') }}</p>
                        </div>
                        <a href="{{ route('billing.subscription', ['tenant' => $tenant->slug]) }}" class="text-sm font-semibold underline underline-offset-4">
                            {{ __('app.manage_billing') }}
                        </a>
                    </div>

                    @if ($latestSubscription)
                        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
                            <div class="grid flex-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <p class="text-xs text-slate-500">{{ __('app.plan') }}</p>
                                    <p class="mt-1 font-semibold">{{ $subscriptionPlan }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">{{ __('app.status') }}</p>
                                    <p class="mt-1 font-semibold">{{ str($latestSubscription->status->value)->headline() }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">{{ __('app.ends') }}</p>
                                    <p class="mt-1 font-semibold">{{ $latestSubscription->end_at?->format('Y-m-d H:i') ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">{{ __('app.access') }}</p>
                                    <p class="mt-1 font-semibold">{{ $subscriptionUsable ? __('app.subscription_usable') : __('app.subscription_not_usable') }}</p>
                                </div>
                            </div>
                            @if (in_array($latestSubscription->status, [
                                \App\Domain\Billing\Enums\SubscriptionStatus::Trial,
                                \App\Domain\Billing\Enums\SubscriptionStatus::Active,
                                \App\Domain\Billing\Enums\SubscriptionStatus::Suspended,
                            ], true))
                                <form method="POST" action="{{ route('admin.subscriptions.toggle-status', $latestSubscription) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold dark:border-slate-700">
                                        {{ $latestSubscription->status === \App\Domain\Billing\Enums\SubscriptionStatus::Suspended ? __('Activate') : __('Suspend') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                                            @else
                        <div class="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500 dark:border-slate-700">
                            {{ __('app.no_active_subscription') }}
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold">{{ __('app.workspace_usage') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.workspace_usage_help') }}</p>
                        </div>
                        <a href="{{ route('admin.usage.index') }}" class="text-sm font-semibold underline underline-offset-4">
                            {{ __('app.view_usage') }}
                        </a>
                    </div>

                    @if ($usage)
                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold">{{ number_format($usage->unique_customer_count) }} / {{ number_format($usage->included_customer_limit) }} {{ __('app.customers') }}</span>
                                <span class="font-bold">{{ $usagePercent }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-brand-indigo" style="width: {{ $usagePercent }}%"></div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                                <span>{{ __('app.period') }}: {{ $usage->period_start?->format('Y-m-d') }} — {{ $usage->period_end?->format('Y-m-d') }}</span>
                                <span>{{ __('app.additional') }}: {{ number_format($usage->additional_customer_count) }}</span>
                                <span>{{ __('app.charge') }}: {{ number_format($usage->total_charge_minor / 100, 2) }} {{ $usage->currency }}</span>
                            </div>
                        </div>
                    @else
                        <p class="mt-5 text-sm text-slate-500">{{ __('app.no_usage_data') }}</p>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ __('app.recent_bookings') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.recent_bookings_help') }}</p>
                        </div>
                        <a href="{{ route('booking.management.index', ['tenant' => $tenant->slug]) }}" class="text-sm font-semibold underline underline-offset-4">
                            {{ __('app.view_bookings') }}
                        </a>
                    </div>

                    <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($recentBookings as $booking)
                            <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $booking->customer?->name ?? '—' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ data_get($booking->service?->name, app()->getLocale()) ?? data_get($booking->service?->name, 'en') ?? '—' }} · {{ $booking->booking_reference }}</p>
                                </div>
                                <div class="shrink-0 text-start sm:text-end">
                                    <p class="text-sm font-semibold">{{ $booking->starts_at?->format('Y-m-d H:i') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ str($booking->status->value)->headline() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-slate-500">{{ __('app.no_recent_bookings') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div>
                        <h3 class="font-semibold">{{ __('app.workspace_owner') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.workspace_owner_help') }}</p>
                    </div>
                    @if ($ownerUser)
                        <div class="mt-5">
                            <p class="font-semibold">{{ $ownerUser->name }}</p>
                            <p class="mt-1 break-all text-sm text-slate-500">{{ $ownerUser->email }}</p>
                        </div>
                    @else
                        <p class="mt-5 text-sm text-slate-500">—</p>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ __('app.workspace_members') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.workspace_members_help') }}</p>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold underline underline-offset-4">{{ __('app.manage_users') }}</a>
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse ($members as $membership)
                            <div class="rounded-xl border border-slate-200 px-3 py-2.5 dark:border-slate-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $membership->user?->name ?? '—' }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500">
                                            {{ $membership->user?->email ?? '—' }}
                                            ·
                                            {{ $membership->is_primary ? __('app.owner') : str($membership->status->value)->headline() }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.users.membership-toggle', $membership) }}" class="shrink-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-[11px] font-semibold dark:border-slate-700">
                                            {{ $membership->status === \App\Domain\Tenant\Enums\MembershipStatus::Active ? __('Suspend') : __('Activate') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('app.no_workspace_members') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ __('app.workspace_modules') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('app.workspace_modules_help') }}</p>
                        </div>
                        <a href="{{ route('admin.businesses.modules.index', $tenant) }}" class="text-sm font-semibold underline underline-offset-4">{{ __('app.manage_modules') }}</a>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @forelse ($activeModules as $tenantModule)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ data_get($tenantModule->module?->name, app()->getLocale()) ?? $tenantModule->module?->key }}
                            </span>
                        @empty
                            <span class="text-sm text-slate-500">{{ __('app.no_active_modules') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div>
                        <h3 class="font-semibold">{{ __('app.booking_payment_account') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('app.booking_payment_account_help') }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.businesses.payment-account.update', $tenant) }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PATCH')

                        <label class="block space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.merchant_id') }}</span>
                            <input name="merchant_id"
                                   value="{{ old('merchant_id', $tenant->paymentAccount?->merchant_id) }}"
                                   placeholder="MID-..."
                                   pattern="MID-[A-Z0-9-]+"
                                   required
                                   class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                        </label>

                        <label class="block space-y-1.5 text-sm">
                            <span class="font-semibold">{{ __('app.connection_status') }}</span>
                            <select name="status" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 dark:border-slate-700 dark:bg-slate-950">
                                @foreach ([
                                    'pending' => __('app.pending_verification'),
                                    'active' => __('app.active'),
                                    'disabled' => __('app.suspended'),
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected($tenant->paymentAccount?->status?->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs leading-5 text-slate-500">{{ __('app.payment_account_active_help') }}</p>
                            <button type="submit" class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white">
                                {{ __('app.save_payment_account') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold">{{ __('platform.customer_intelligence') }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('platform.customer_intelligence_help') }}</p>
                        </div>
                        <a href="{{ route('admin.customers.index', ['tenant_id' => $tenant->id]) }}" class="text-sm font-semibold underline underline-offset-4">{{ __('platform.open_customer_data') }}</a>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                            <p class="text-xs text-slate-500">{{ __('platform.customers') }}</p>
                            <p class="mt-1 text-xl font-bold">{{ number_format($stats['customers']) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                            <p class="text-xs text-slate-500">{{ __('platform.paid_revenue') }}</p>
                            <p class="mt-1 text-xl font-bold">{{ number_format($stats['paidRevenueMinor'] / 100, 2) }} {{ $latestSubscription?->currency ?? 'EGP' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div>
                        <h3 class="font-semibold">{{ __('platform.member_management') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('platform.member_management_help') }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.businesses.members.store', $tenant) }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm">
                                <span class="font-semibold">{{ __('platform.member_name') }}</span>
                                <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="block text-sm">
                                <span class="font-semibold">{{ __('platform.member_email') }}</span>
                                <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="block text-sm">
                                <span class="font-semibold">{{ __('platform.member_password') }}</span>
                                <input type="password" name="password" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                                <span class="mt-1 block text-xs text-slate-500">{{ __('platform.member_password_help') }}</span>
                            </label>
                            <label class="block text-sm">
                                <span class="font-semibold">{{ __('platform.member_password_confirmation') }}</span>
                                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="block text-sm">
                                <span class="font-semibold">{{ __('platform.role') }}</span>
                                <select name="role" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                                    @foreach(array_keys(config('bookresa.rbac.roles', [])) as $role)
                                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ str($role)->headline() }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="inline-flex items-center gap-2 self-end text-sm font-semibold">
                                <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary'))>
                                {{ __('platform.make_primary_owner') }}
                            </label>
                        </div>
                        <button class="rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                            {{ __('platform.add_member') }}
                        </button>
                    </form>

                    <div class="mt-6 space-y-3">
                        @forelse($members as $membership)
                            @php
                                $memberRole = $memberRoles[$membership->getKey()] ?? '—';
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold">{{ $membership->user?->name ?? '—' }}</p>
                                            @if($membership->is_primary)
                                                <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-bold text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-200">{{ __('platform.primary_owner') }}</span>
                                            @endif
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $memberRole }}</span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ str($membership->status->value)->headline() }}</span>
                                        </div>
                                        <p class="mt-1 break-all text-xs text-slate-500">{{ $membership->user?->email ?? '—' }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 xl:shrink-0">
                                        @php($membership->user?->loadMissing('platformAdmin'))
                                        @if ($membership->user && ! $membership->user->platformAdmin?->is_active)
                                            <form method="POST" action="{{ route('admin.businesses.impersonate', [$tenant, $membership->user]) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-bold text-indigo-700 dark:border-indigo-900 dark:text-indigo-300">
                                                    {{ __('platform.impersonate') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('admin.businesses.members.update', [$tenant, $membership]) }}" class="grid gap-2 sm:grid-cols-3 xl:min-w-[34rem]">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">
                                            @foreach(array_keys(config('bookresa.rbac.roles', [])) as $role)
                                                <option value="{{ $role }}" @selected($memberRole === $role)>{{ str($role)->headline() }}</option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">
                                            <option value="active" @selected($membership->status->value === 'active')>{{ __('Active') }}</option>
                                            <option value="suspended" @selected($membership->status->value === 'suspended')>{{ __('Suspended') }}</option>
                                        </select>
                                        <label class="flex items-center gap-2 rounded-lg border border-slate-300 px-2.5 py-2 text-xs font-semibold dark:border-slate-700">
                                            <input type="checkbox" name="is_primary" value="1" @checked($membership->is_primary)>
                                            {{ __('platform.primary_owner') }}
                                        </label>
                                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white dark:bg-white dark:text-slate-900">{{ __('platform.save_member') }}</button>
                                    </form>

                                    @unless($membership->is_primary)
                                        <form method="POST" action="{{ route('admin.businesses.members.destroy', [$tenant, $membership]) }}" onsubmit="return confirm(@js(__('platform.remove_member_confirm')))">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 dark:border-rose-700 dark:text-rose-300">{{ __('platform.remove_member') }}</button>
                                        </form>
                                    @endunless
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">{{ __('app.no_workspace_members') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold">{{ __('app.manage_workspace') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.manage_workspace_help') }}</p>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                        <a href="{{ route('business.profile.edit', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.business_profile') }}</a>
                        <a href="{{ route('services.index', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.services') }}</a>
                        <a href="{{ route('staff.index', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.staff') }}</a>
                        <a href="{{ route('customers.index', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.customers') }}</a>
                        <a href="{{ route('calendar.index', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.calendar') }}</a>
                        <a href="{{ route('billing.subscription', ['tenant' => $tenant->slug]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold dark:border-slate-800">{{ __('app.billing') }}</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
