@extends('layouts.admin')

@section('title', __('Payment').' — BookResa')
@section('heading', __('Payment'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-mono text-slate-500">{{ $payment->reference }}</p><h2 class="mt-1 text-2xl font-bold">{{ __('Payment details') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $payment->provider }} · {{ $payment->currency }}</p></div>
        <a href="{{ route('admin.payments.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Back') }}</a>
    </div>
    <section class="br-panel p-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><p class="text-xs text-slate-500">{{ __('Amount') }}</p><p class="mt-1 text-xl font-bold">{{ number_format($payment->amount_minor / 100, 2) }} {{ $payment->currency }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('Status') }}</p><p class="mt-1 font-bold">{{ str($payment->status->value)->headline() }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('Provider reference') }}</p><p class="mt-1 break-all font-mono text-sm">{{ $payment->provider_reference ?: '—' }}</p></div>
            <div><p class="text-xs text-slate-500">{{ __('Workspace') }}</p><p class="mt-1 font-semibold">{{ data_get($payment->tenant?->profile?->name, app()->getLocale()) ?? $payment->tenant?->slug }}</p></div>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            @if($payment->provider_reference)
                <form method="POST" action="{{ route('admin.payments.verify', $payment->id) }}">@csrf<button class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold dark:border-slate-700">{{ __('Verify with provider') }}</button></form>
            @endif
            @if($payment->status === \App\Domain\Payment\Enums\PaymentStatus::Paid)
                <form method="POST" action="{{ route('admin.payments.refund', $payment->id) }}">@csrf<button onclick="return confirm('{{ __('Refund this full payment?') }}')" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white">{{ __('Refund full payment') }}</button></form>
            @endif
        </div>
    </section>
    <section class="br-panel p-5">
        <h3 class="font-bold">{{ __('Payment metadata') }}</h3>
        <pre class="mt-4 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-200">{{ json_encode($payment->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>
</div>
@endsection
