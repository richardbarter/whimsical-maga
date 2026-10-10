<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Sharing tags, set per page with withViewData('meta', …). There's no SSR, so bots only see these. --}}
        @isset($meta)
            <meta name="description" content="{{ $meta['description'] }}">
            <link rel="canonical" href="{{ $meta['url'] }}">
            <meta property="og:site_name" content="{{ config('app.name') }}">
            <meta property="og:type" content="article">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            <meta property="og:url" content="{{ $meta['url'] }}">
            <meta name="twitter:card" content="summary">
        @endisset

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=nunito:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @php(Vite::useCspNonce(app('csp-nonce')))
        @routes(nonce: app('csp-nonce'))
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
