<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Business — {{ config('bookresa.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <main class="mx-auto max-w-2xl px-6 py-12">
        <h1 class="text-3xl font-semibold">Create your business</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Create an isolated Velto workspace and choose your business type.
        </p>

        <form method="POST" action="{{ route('onboarding.business.store') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium">Business name</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="120"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium">Public URL slug</label>
                <input id="slug" name="slug" value="{{ old('slug') }}" maxlength="120"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="business_type_id" class="block text-sm font-medium">Business type</label>
                <select id="business_type_id" name="business_type_id" required
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <option value="">Select a type</option>
                    @foreach ($businessTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('business_type_id') == $type->id)>
                            {{ $type->name['en'] ?? $type->slug }}
                        </option>
                    @endforeach
                </select>
                @error('business_type_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-black px-5 py-3 font-medium text-white hover:opacity-90 dark:bg-white dark:text-black">
                Create Workspace
            </button>
        </form>
    </main>
</body>
</html>
