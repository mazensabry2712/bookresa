@extends('layouts.admin')

@section('title', __('Businesses').' — BookResa')
@section('heading', __('Businesses'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform workspaces') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Businesses') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Review tenant status, team size and workspace activity.') }}</p>
        </div>

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search by business name, slug or email') }}"
                   class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Search') }}</button>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('Business') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Type') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Members') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Services') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Staff') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($businesses as $business)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ data_get($business->profile?->name, app()->getLocale()) ?? $business->slug }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $business->slug }} · {{ $business->profile?->email ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-500">{{ data_get($business->businessType?->name, app()->getLocale()) ?? $business->businessType?->slug ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $business->status === \App\Domain\Tenant\Enums\TenantStatus::Active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200' }}">
                                        {{ str($business->status->value)->headline() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">{{ number_format($business->memberships_count) }}</td>
                                <td class="px-5 py-4">{{ number_format($business->services_count) }}</td>
                                <td class="px-5 py-4">{{ number_format($business->staff_profiles_count) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('admin.businesses.modules.index', $business) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">{{ __('Modules') }}</a>
                                        <form method="POST" action="{{ route('admin.businesses.toggle-status', $business) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">
                                                {{ $business->status === \App\Domain\Tenant\Enums\TenantStatus::Suspended ? __('Activate') : __('Suspend') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No businesses found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($businesses->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $businesses->links() }}</div>
            @endif
        </section>
    </div>
@endsection
