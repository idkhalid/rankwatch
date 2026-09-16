<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? 'RankWatch' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-950 antialiased">
<main class="grid min-h-screen lg:grid-cols-[1fr_520px]">
    <section class="hidden border-r border-slate-200 bg-white px-10 py-8 lg:flex lg:flex-col">
        <a href="{{ route('marketing.home') }}" class="text-lg font-bold tracking-tight">RankWatch</a>
        <div class="flex flex-1 items-center">
            <div class="max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">SEO monitoring</p>
                <p class="mt-4 text-4xl font-bold tracking-tight">Track rankings. Catch SEO issues. See what changed.</p>
                <p class="mt-4 text-lg text-slate-600">A focused workspace for site owners and small teams: keyword status, crawl health, current issues, and simple reports.</p>
                <div class="mt-8 grid gap-3 text-sm text-slate-700 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Rankings</p>
                        <p class="mt-1">Current and previous positions.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Crawls</p>
                        <p class="mt-1">Technical SEO checks.</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Reports</p>
                        <p class="mt-1">Readable project summaries.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
        <div class="w-full max-w-md">
            <a href="{{ route('marketing.home') }}" class="mb-8 inline-flex text-lg font-bold tracking-tight lg:hidden">RankWatch</a>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/50 sm:p-8">
                @isset($heading)
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold tracking-tight">{{ $heading }}</h1>
                        @isset($subheading)
                            <p class="mt-2 text-sm text-slate-600">{{ $subheading }}</p>
                        @endisset
                    </div>
                @endisset

                {{ $slot }}
            </div>
        </div>
    </section>
</main>
</body>
</html>
