<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'RankWatch - Simple SEO monitoring' }}</title>
    <meta name="description" content="{{ $description ?? 'Simple SEO monitoring for websites that want to grow.' }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'RankWatch' }}">
    <meta property="og:description" content="{{ $description ?? 'Simple SEO monitoring for websites that want to grow.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index,follow">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-slate-900">
<header class="border-b border-slate-200">
    <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('marketing.home') }}" class="text-lg font-bold">RankWatch</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('marketing.pricing') }}" class="text-slate-600 hover:text-slate-950">Pricing</a>
            <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-950">Login</a>
            <a href="{{ route('register') }}" class="rounded-md bg-slate-950 px-3 py-2 font-medium text-white">Register</a>
        </div>
    </nav>
</header>
<main>{{ $slot }}</main>
<footer class="border-t border-slate-200 py-8">
    <div class="mx-auto max-w-6xl px-4 text-sm text-slate-500 sm:px-6 lg:px-8">RankWatch keeps SEO monitoring simple for small teams.</div>
</footer>
</body>
</html>
