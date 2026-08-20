<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">

        {{--
            Link previews. Facebook, Messenger and Instagram read Open Graph;
            X reads the twitter:* tags and falls back to Open Graph. None of
            them run JavaScript, so these live here rather than in an Inertia
            <Head> — a crawler only ever sees what this Blade file renders.
        --}}
        @php
            $shareTitle = 'Creative Abilities Therapy Services — Empowering Every Child, Embracing Every Ability.';
            $shareDescription = 'We support children in building confidence and essential skills, working closely with families so they thrive at home and in their community.';
            $shareImage = asset('images/1920x1080/photo_playing_1920x1080.jpg');
            $shareImageAlt = 'A therapist and a child playing together during a session.';
        @endphp

        <meta name="description" content="{{ $shareDescription }}">

        <meta property="og:site_name" content="Creative Abilities Therapy Services">
        <meta property="og:title" content="{{ $shareTitle }}">
        <meta property="og:description" content="{{ $shareDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="en_CA">
        <meta property="og:image" content="{{ $shareImage }}">
        <meta property="og:image:width" content="1920">
        <meta property="og:image:height" content="1080">
        <meta property="og:image:alt" content="{{ $shareImageAlt }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $shareTitle }}">
        <meta name="twitter:description" content="{{ $shareDescription }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
        <meta name="twitter:image:alt" content="{{ $shareImageAlt }}">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
