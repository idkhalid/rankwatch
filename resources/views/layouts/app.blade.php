<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title ?? config('app.name', 'RankWatch') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900">
<div class="min-h-screen bg-slate-50 lg:flex">
    <aside class="border-b border-slate-200 bg-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-4 lg:block">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold">RankWatch</a>
            <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                @csrf
                <button class="text-sm text-slate-600">Logout</button>
            </form>
        </div>
        <nav class="flex gap-1 overflow-x-auto px-3 pb-4 text-sm lg:block lg:space-y-1">
            <a href="{{ route('dashboard') }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Dashboard</a>
            <a href="{{ route('projects.index') }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Projects</a>
            @isset($project)
                <a href="{{ route('keywords.index', $project) }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Keywords</a>
                <a href="{{ route('projects.issues.index', $project) }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Issues</a>
                <a href="{{ route('projects.report', $project) }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Reports</a>
            @endisset
            <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 hover:bg-slate-100">Settings</a>
        </nav>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ isset($project) ? $project->domain : 'SEO monitoring' }}</p>
                    <h1 class="text-2xl font-semibold">{{ $heading ?? 'Dashboard' }}</h1>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                    @csrf
                    <button class="text-sm text-slate-600 hover:text-slate-950">Logout</button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
