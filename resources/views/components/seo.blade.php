@props([
    'title' => config('app.name', 'Velto'),
    'description' => null,
    'canonical' => url()->current(),
    'robots' => 'index,follow',
    'ogType' => 'website',
    'ogImage' => null,
    'jsonLd' => null,
    'alternates' => [],
])

@php
    $ogLocale = match (app()->getLocale()) {
        'ar' => 'ar_AR',
        default => 'en_US',
    };
@endphp

<title>{{ $title }}</title>

@if (filled($description))
    <meta name="description" content="{{ $description }}">
@endif

<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
@if (filled($description))
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ config('app.name', 'Velto') }}">
<meta property="og:locale" content="{{ $ogLocale }}">

@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif

@foreach ($alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate['locale'] }}" href="{{ $alternate['url'] }}">
@endforeach

@if (filled($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
