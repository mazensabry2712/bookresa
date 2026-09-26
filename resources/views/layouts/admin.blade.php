<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BookResa Admin')</title>
    <meta name="robots" content="noindex,nofollow,noarchive">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="min-h-screen lg:flex">
        <aside class="border-b border-slate-200 bg-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-e dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-16 items-center px-5">
                <a href="{{ route('admin.plans.index') }}" class="text-lg font-bold tracking-tight">BookResa Admin</a>
            </div>
            <nav class="space-y-1 p-3">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold dark:bg-slate-800">{{ __('Dashboard') }}</a>
                <a href="{{ route('admin.businesses.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Businesses') }}</a>
                <a href="{{ route('admin.subscriptions.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Subscriptions') }}</a>
                <a href="{{ route('admin.payments.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Payments') }}</a>
                <a href="{{ route('admin.usage.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Usage') }}</a>
                <a href="{{ route('admin.plans.index') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Plans') }}</a>
                <a href="{{ route('home') }}" class="block rounded-xl px-3 py-2.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800">{{ __('Public site') }}</a>
            </nav>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400">BookResa</p>
                        <h1 class="text-lg font-semibold">@yield('heading', __('Admin'))</h1>
                    </div>
                    <div class="text-end">
                        <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </header>
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200">
                        <ul class="space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
