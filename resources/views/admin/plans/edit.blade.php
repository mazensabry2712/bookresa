@extends('layouts.admin')

@section('title', __('Edit plan').' — BookResa')
@section('heading', __('Edit plan'))

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div><p class="text-sm text-slate-500">#{{ $plan->id }}</p><h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Edit subscription plan') }}</h2></div>
    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">
        @csrf @method('PUT')
        @include('admin.plans._form', ['submitLabel' => __('Save changes')])
    </form>
</div>
@endsection
