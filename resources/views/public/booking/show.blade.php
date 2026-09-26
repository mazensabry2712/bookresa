<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $profile = $tenant->profile;
        $locale = app()->getLocale();
        $businessName = $profile?->name[$locale] ?? $profile?->name['en'] ?? $tenant->slug;
        $businessDescription = $profile?->description[$locale] ?? $profile?->description['en'] ?? 'Book an appointment online.';
        $canonicalUrl = route('public.booking.show', $tenant->slug);
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $businessName,
            'description' => $businessDescription,
            'url' => $canonicalUrl,
            'potentialAction' => [
                '@type' => 'ReserveAction',
                'target' => $canonicalUrl,
            ],
        ];

        if ($profile?->phone) {
            $jsonLd['telephone'] = $profile->phone;
        }

        if ($profile?->email) {
            $jsonLd['email'] = $profile->email;
        }

        if ($profile?->address) {
            $jsonLd['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $profile->address,
            ];
        }

        if ($services->isNotEmpty()) {
            $jsonLd['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => 'Services',
                'itemListElement' => $services->map(fn ($service) => [
                    '@type' => 'Offer',
                    'price' => number_format($service->price_minor / 100, 2, '.', ''),
                    'priceCurrency' => $service->currency,
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $service->name[$locale] ?? $service->name['en'] ?? 'Service',
                    ],
                ])->values()->all(),
            ];
        }
    @endphp

    <x-seo
        :title="$businessName.' — BookResa'"
        :description="$businessDescription"
        :canonical="$canonicalUrl"
        :json-ld="$jsonLd"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
    <main class="mx-auto max-w-3xl px-5 py-10" x-data="bookingPage()">
        <header class="mb-8">
            <p class="text-sm text-gray-500">BookResa</p>
            <h1 class="mt-2 text-4xl font-bold">{{ $tenant->profile?->name['en'] ?? $tenant->slug }}</h1>
            @if ($tenant->profile?->description['en'])
                <p class="mt-3 text-gray-600 dark:text-gray-400">{{ $tenant->profile->description['en'] }}</p>
            @endif
        </header>

        @php
            $staffByService = $services->mapWithKeys(fn ($service) => [
                $service->id => $service->staff->map(fn ($staff) => [
                    'id' => $staff->id,
                    'display_name' => $staff->display_name,
                ])->values()->all(),
            ])->all();
        @endphp

        <form method="POST" action="{{ route('public.booking.store', $tenant->slug) }}" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @csrf

            @error('booking')
                <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
            @enderror

            <div>
                <label for="service_id" class="block text-sm font-medium">Service</label>
                <select id="service_id" name="service_id" x-model="serviceId" @change="loadAvailability"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
                    <option value="">Select a service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}">
                            {{ $service->name['en'] ?? 'Service' }} — {{ number_format($service->price_minor / 100, 2) }} {{ $service->currency }} — {{ $service->duration_minutes }} min
                        </option>
                    @endforeach
                </select>
                @error('service_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="date" class="block text-sm font-medium">Date</label>
                <input id="date" type="date" name="date" x-model="date" @change="loadAvailability" min="{{ $today }}"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
                @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div x-show="staffs.length" x-cloak>
                <label for="staff_id" class="block text-sm font-medium">Staff</label>
                <select id="staff_id" name="staff_id" x-model="staffId" @change="loadAvailability"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
                    <option value="">Choose automatically</option>
                    <template x-for="staff in staffs" :key="staff.id">
                        <option :value="staff.id" x-text="staff.display_name"></option>
                    </template>
                </select>
            </div>

            <div>
                <p class="text-sm font-medium">Available times</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <template x-for="slot in slots" :key="slot.time + '-' + (slot.staff_id ?? 'auto')">
                        <button type="button" @click="selectedTime = slot.time"
                            class="rounded-lg border px-3 py-2 text-sm"
                            :class="selectedTime === slot.time ? 'border-black bg-black text-white' : 'border-gray-300 dark:border-gray-700'">
                            <span x-text="slot.time"></span>
                        </button>
                    </template>
                </div>
                <p x-show="loading" class="mt-3 text-sm text-gray-500">Loading availability…</p>
                <p x-show="!loading && serviceId && date && !slots.length" class="mt-3 text-sm text-gray-500">No available times.</p>
                <input type="hidden" name="time" x-model="selectedTime">
                @error('time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium">Name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required
                        class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}"
                        class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="mt-2 block w-full rounded-lg border-gray-300 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-950">
            </div>

            <button type="submit" :disabled="!serviceId || !date || !selectedTime"
                class="w-full rounded-lg bg-black px-5 py-3 font-medium text-white disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-black">
                Book Appointment
            </button>
        </form>

        <script>
            function bookingPage() {
                return {
                    serviceId: '',
                    staffId: '',
                    date: '',
                    selectedTime: '',
                    slots: [],
                    staffs: [],
                    staffByService: @js($staffByService),
                    loading: false,
                    async loadAvailability() {
                        this.selectedTime = '';
                        this.staffs = this.staffByService[this.serviceId] ?? [];
                        if (this.staffId && !this.staffs.some(staff => String(staff.id) === String(this.staffId))) {
                            this.staffId = '';
                        }
                        if (!this.serviceId || !this.date) {
                            this.slots = [];
                            return;
                        }
                        this.loading = true;
                        const params = new URLSearchParams({
                            service_id: this.serviceId,
                            date: this.date,
                        });
                        if (this.staffId) params.set('staff_id', this.staffId);
                        const response = await fetch(@js(route('public.booking.availability', $tenant->slug)) + '?' + params.toString());
                        const payload = await response.json();
                        this.slots = payload.data ?? [];
                        this.loading = false;
                    },
                };
            }
        </script>
    </main>
</body>
</html>
