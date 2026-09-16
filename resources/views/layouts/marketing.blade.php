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
<body class="bg-white font-sans text-slate-950">
<header class="border-b border-slate-200 bg-white/95">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('marketing.home') }}" class="text-lg font-bold tracking-tight">RankWatch</a>
        <div class="flex items-center gap-4 text-sm">
            <a href="{{ route('marketing.pricing') }}" class="font-medium text-slate-600 hover:text-slate-950">Pricing</a>
            <a href="{{ route('login') }}" class="font-medium text-slate-600 hover:text-slate-950">Sign in</a>
            <a href="{{ route('register') }}" class="rounded-lg bg-slate-950 px-3 py-2 font-semibold text-white hover:bg-slate-800">Start Free</a>
        </div>
    </nav>
</header>
<main>{{ $slot }}</main>
<footer class="border-t border-slate-200 py-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <p>RankWatch keeps SEO monitoring simple for small teams.</p>
        <a href="{{ route('marketing.pricing') }}" class="font-medium text-slate-600 hover:text-slate-950">View plans</a>
    </div>
</footer>
</body>
</html>
