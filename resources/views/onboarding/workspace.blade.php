<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo
        :title="($tenant->profile?->name[app()->getLocale()] ?? $tenant->profile?->name['en'] ?? $tenant->slug).' — '.config('bookresa.name', 'BookResa')"
        :description="'Workspace for '.$tenant->slug.'.'"
        robots="noindex,nofollow,noarchive"
    />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <main class="mx-auto max-w-4xl px-6 py-12">
        <p class="text-sm text-gray-500">Workspace</p>
        <h1 class="mt-2 text-4xl font-semibold">
            {{ $tenant->profile->name['en'] ?? $tenant->slug }}
        </h1>
        <p class="mt-3 text-gray-600 dark:text-gray-400">
            Public slug: <strong>{{ $tenant->slug }}</strong>
        </p>

        <section class="mt-8 grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-medium">Business Type</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $tenant->businessType?->name['en'] ?? $tenant->businessType?->slug }}
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-medium">Enabled Modules</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ $tenant->modules->pluck('key')->join(', ') ?: 'None' }}
                </p>
            </div>
        </section>
    </main>
</body>
</html>
