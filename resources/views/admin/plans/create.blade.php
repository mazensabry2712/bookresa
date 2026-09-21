@extends('layouts.admin')

@section('title', __('Create plan').' — BookResa')
@section('heading', __('Create plan'))

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div><h2 class="text-2xl font-bold tracking-tight">{{ __('Create subscription plan') }}</h2><p class="mt-1 text-sm text-slate-500">{{ __('All monetary values are stored in minor units for deterministic billing.') }}</p></div>
    <form method="POST" action="{{ route('admin.plans.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        @csrf
        @include('admin.plans._form', ['submitLabel' => __('Create plan')])
    </form>
</div>
@endsection
