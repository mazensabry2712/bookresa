@extends('layouts.admin')

@section('title', __('Users').' — BookResa')
@section('heading', __('Users'))

@section('content')
    <div class="space-y-6">
        <div>
            <p class="text-sm text-slate-500">{{ __('Platform users') }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Users') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Manage platform access, tenant memberships and administrator access.') }}</p>
        </div>

        <form method="GET" class="flex flex-col gap-3 sm:flex-row">
            <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or email') }}"
                   class="min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Search') }}</button>
        </form>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('User') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Platform admin') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Workspaces') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Membership status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ $user->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->platformAdmin?->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ $user->platformAdmin?->is_active ? __('Active') : __('No') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ number_format($user->active_memberships_count) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ __('active memberships') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="min-w-[18rem] space-y-2">
                                        @forelse ($user->tenantMemberships as $membership)
                                            @php
                                                $businessName = data_get($membership->tenant?->profile?->name, app()->getLocale())
                                                    ?? data_get($membership->tenant?->profile?->name, 'en')
                                                    ?? $membership->tenant?->slug
                                                    ?? '—';
                                            @endphp
                                            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-800">
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">{{ $businessName }}</p>
                                                    <p class="text-xs text-slate-500">{{ str($membership->status->value)->headline() }}</p>
                                                </div>
                                                <form method="POST" action="{{ route('admin.users.membership-toggle', $membership) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="whitespace-nowrap rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold dark:border-slate-700">
                                                        {{ $membership->status === \App\Domain\Tenant\Enums\MembershipStatus::Active ? __('Suspend') : __('Activate') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-500">{{ __('No workspace memberships.') }}</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <form method="POST" action="{{ route('admin.users.platform-admin-toggle', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold dark:border-slate-700">
                                            {{ $user->platformAdmin?->is_active ? __('Disable admin') : __('Make platform admin') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">{{ __('No users found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="border-t border-slate-200 px-5 py-4 dark:border-slate-800">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
@endsection
