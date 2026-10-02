@extends('layouts.admin')

@section('title', __('User').' — BookResa')
@section('heading', __('User'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('Central user security and platform access control.') }}</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Back') }}</a>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="br-panel p-5">
                <h3 class="font-bold">{{ __('Profile') }}</h3>
                @if(auth()->user()?->platformAdmin?->hasPlatformPermission('users.manage'))
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Name') }}</span><input name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Email') }}</span><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white dark:bg-white dark:text-slate-900">{{ __('Save profile') }}</button>
                </form>
                @endif
            </section>

            <section class="br-panel p-5">
                <h3 class="font-bold">{{ __('Account security') }}</h3>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4"><span>{{ __('Email verification') }}</span><span class="font-semibold">{{ $user->email_verified_at ? __('Verified') : __('Unverified') }}</span></div>
                    <div class="flex items-center justify-between gap-4"><span>{{ __('Two-factor authentication') }}</span><span class="font-semibold">{{ $user->two_factor_confirmed_at ? __('Enabled') : __('Disabled') }}</span></div>
                </div>
                @if(auth()->user()?->platformAdmin?->hasPlatformPermission('users.manage'))
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <form method="POST" action="{{ route('admin.users.verify-email', $user) }}">@csrf<button class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Mark email verified') }}</button></form>
                    <form method="POST" action="{{ route('admin.users.revoke-sessions', $user) }}">@csrf<button class="w-full rounded-xl border border-rose-300 px-4 py-2.5 text-sm font-semibold text-rose-700 dark:border-rose-900 dark:text-rose-300">{{ __('Revoke sessions') }}</button></form>
                    <form method="POST" action="{{ route('admin.users.disable-two-factor', $user) }}">@csrf<button class="w-full rounded-xl border border-amber-300 px-4 py-2.5 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:text-amber-300">{{ __('Disable 2FA') }}</button></form>
                </div>
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-5 space-y-3">
                    @csrf
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('New password') }}</span><input type="password" name="password" required minlength="12" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Confirm password') }}</span><input type="password" name="password_confirmation" required minlength="12" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950"></label>
                    <button class="rounded-xl border border-amber-300 px-4 py-2.5 text-sm font-semibold text-amber-800 dark:border-amber-900 dark:text-amber-300">{{ __('Reset password and revoke sessions') }}</button>
                </form>
                @endif
            </section>
        </div>

        <section class="br-panel p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><h3 class="font-bold">{{ __('Platform administrator access') }}</h3><p class="mt-1 text-sm text-slate-500">{{ __('Use a preset role and optional extra permissions. Super Admin keeps full platform and tenant control.') }}</p></div>
                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold dark:bg-slate-800">{{ $platformAdmin?->roleLabel() ?? __('Not a platform admin') }}</span>
            </div>

            @if(auth()->user()?->platformAdmin?->hasPlatformPermission('security.manage'))
            <form method="POST" action="{{ route('admin.users.platform-admin-access', $user) }}" class="mt-5 space-y-5">
                @csrf
                @method('PATCH')
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm"><span class="mb-1 block font-medium">{{ __('Role') }}</span>
                        <select name="role" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($platformRoles as $roleKey => $role)
                                <option value="{{ $roleKey }}" @selected(old('role', $platformAdmin?->role ?? 'viewer') === $roleKey)>{{ $role['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="flex items-end gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-800">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $platformAdmin?->is_active ?? false)) class="h-4 w-4">
                        <span><span class="block font-semibold">{{ __('Active platform access') }}</span><span class="text-xs text-slate-500">{{ __('Disabled admins cannot enter /admin.') }}</span></span>
                    </label>
                </div>

                <div>
                    <p class="text-sm font-semibold">{{ __('Additional custom permissions') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($platformPermissions as $permission)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs dark:border-slate-800">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', $platformAdmin?->permissions ?? []), true)) class="h-4 w-4">
                                <span>{{ $permission }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button class="rounded-xl bg-brand-indigo px-5 py-2.5 text-sm font-semibold text-white">{{ __('Save platform access') }}</button>
            </form>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="br-panel p-5">
                <h3 class="font-bold">{{ __('Workspace memberships') }}</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($user->tenantMemberships as $membership)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                            <div><p class="font-semibold">{{ data_get($membership->tenant?->profile?->name, app()->getLocale()) ?? $membership->tenant?->slug }}</p><p class="text-xs text-slate-500">{{ $membership->status->value }} @if($membership->is_primary) · {{ __('Owner') }} @endif</p></div>
                            @if ($membership->tenant)<a href="{{ route('admin.businesses.show', $membership->tenant) }}" class="text-xs font-bold text-brand-indigo hover:underline">{{ __('Open workspace') }}</a>@endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No memberships.') }}</p>
                    @endforelse
                </div>
            </section>
            <section class="br-panel p-5">
                <h3 class="font-bold">{{ __('Recent security events') }}</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($securityEvents as $event)
                        <div class="rounded-xl border border-slate-200 px-4 py-3 dark:border-slate-800">
                            <p class="text-sm font-semibold">{{ $event->description }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $event->created_at?->toIso8601String() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No security events.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
