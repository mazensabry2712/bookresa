@extends('layouts.dashboard')

@section('title', __('app.notification_ui.notifications').' — '.config('app.name', 'BookResa'))
@section('heading', __('app.notification_ui.notifications'))

@section('content')
    @php
        $localized = static fn (?array $values): string => (string) (
            data_get($values, app()->getLocale())
            ?? data_get($values, 'en')
            ?? data_get($values, 'ar')
            ?? '—'
        );

        $typeLabels = [
            'booking_created' => __('app.notification_ui.booking'),
            'booking_confirmed' => __('app.notification_ui.booking'),
            'booking_cancelled' => __('app.notification_ui.booking'),
            'booking_rescheduled' => __('app.notification_ui.booking'),
            'booking_reminder' => __('app.notification_ui.booking'),
            'business_booking_created' => __('app.notification_ui.booking'),
            'business_booking_cancelled' => __('app.notification_ui.booking'),
            'payment_paid' => __('app.notification_ui.payment'),
            'payment_failed' => __('app.notification_ui.payment'),
            'business_payment_paid' => __('app.notification_ui.payment'),
            'subscription_expiring' => __('app.notification_ui.subscription'),
            'usage_warning' => __('app.notification_ui.usage'),
        ];

        $typeClasses = [
            'booking_created' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
            'booking_confirmed' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'booking_cancelled' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            'booking_rescheduled' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
            'booking_reminder' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
            'business_booking_created' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
            'business_booking_cancelled' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            'payment_paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'payment_failed' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
            'business_payment_paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
            'subscription_expiring' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
            'usage_warning' => 'bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-200',
        ];
    @endphp

    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-brand-indigo">{{ $tenant->slug }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 dark:text-white">{{ __('app.notification_ui.title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ __('app.notification_ui.page_help') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    {{ trans_choice('app.notification_ui.unread_count', $unreadCount, ['count' => $unreadCount]) }}
                </span>

                @if ($unreadCount > 0)
                    @can('notifications.view')
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-brand-navy px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">
                                {{ __('app.notification_ui.mark_all_read') }}
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </section>

        <section class="br-panel overflow-hidden">
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $type = (string) data_get($data, 'type', '');
                        $title = $localized(data_get($data, 'title'));
                        $message = $localized(data_get($data, 'message'));
                        $category = $typeLabels[$type] ?? __('app.notification_ui.other');
                        $categoryClass = $typeClasses[$type] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                    @endphp

                    <div class="flex gap-4 px-5 py-5 sm:px-6 {{ $notification->read_at ? 'bg-white dark:bg-slate-900' : 'bg-indigo-50/30 dark:bg-indigo-950/10' }}">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $categoryClass }}" aria-hidden="true">
                            @if (str_contains($type, 'payment'))
                                $
                            @elseif (str_contains($type, 'subscription') || str_contains($type, 'usage'))
                                !
                            @else
                                •
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $categoryClass }}">{{ $category }}</span>
                                        @unless ($notification->read_at)
                                            <span class="h-2 w-2 rounded-full bg-brand-indigo" title="{{ __('app.notification_ui.unread') }}" aria-label="{{ __('app.notification_ui.unread') }}"></span>
                                        @endunless
                                    </div>
                                    <h3 class="mt-2 text-sm font-bold text-slate-950 dark:text-white">{{ $title }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-500">{{ $message }}</p>
                                </div>

                                <time class="shrink-0 text-xs font-semibold text-slate-400" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                    {{ $notification->created_at?->setTimezone(data_get($tenant->profile, 'timezone', config('app.timezone', 'UTC')))->diffForHumans() }}
                                </time>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                @if ($reference = data_get($data, 'booking_reference'))
                                    <span class="font-mono font-bold">{{ $reference }}</span>
                                @endif
                                @if ($amountMinor = data_get($data, 'amount_minor'))
                                    <span>{{ number_format($amountMinor / 100, 2) }} {{ data_get($data, 'currency', '') }}</span>
                                @endif
                                @if ($stage = data_get($data, 'stage'))
                                    <span>{{ str($stage)->replace('_', ' ')->headline() }}</span>
                                @endif
                            </div>

                            <div class="mt-4">
                                @can('notifications.view')
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-brand-indigo hover:underline">
                                            {{ $notification->read_at ? __('app.notification_ui.view_again') : __('app.notification_ui.open_notification') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-navy dark:bg-slate-800 dark:text-indigo-300" aria-hidden="true">✓</div>
                        <h3 class="mt-4 font-bold text-slate-950 dark:text-white">{{ __('app.notification_ui.no_notifications') }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('app.notification_ui.no_notifications_help') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
