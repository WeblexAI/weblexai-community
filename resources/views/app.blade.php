<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'WeblexAI') }} - Dashboard</title>

    <!-- SEO Meta Tags (Dashboard - Private) -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <meta name="description"
        content="WeblexAI Dashboard - Manage your multilingual website translations with AI-powered localization tools.">

    <!-- Security Headers -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- PWA Meta Tags -->
    <meta name="application-name" content="WeblexAI Dashboard">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="WeblexAI">

    <!-- Favicon and App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ versioned_asset('fav-icon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ versioned_asset('fav-icon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ versioned_asset('fav-icon/favicon-16x16.png') }}">

    <link rel="icon" type="image/png" sizes="512x512"
        href="{{ versioned_asset('fav-icon/android-chrome-512x512.png') }}">
    <link rel="icon" type="image/png" sizes="192x192"
        href="{{ versioned_asset('fav-icon/android-chrome-192x192.png') }}">

    <link rel="manifest" href="{{ versioned_asset('fav-icon/site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ versioned_asset('fav-icon/favicon.ico') }}">

    <meta name="msapplication-TileColor" content="#34a85a">
    <meta name="msapplication-config" content="{{ versioned_asset('fav-icon/browserconfig.xml') }}">
    <meta name="theme-color" content="#34a85a" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1f2937" media="(prefers-color-scheme: dark)">

    <!-- Performance Optimizations -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @routes
    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased bg-gray-50">
    @inertia
</body>

</html>