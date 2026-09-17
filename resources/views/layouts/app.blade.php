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
<body class="bg-slate-50 font-sans antialiased text-slate-950">
<div x-data="{ navOpen: false }" class="min-h-screen lg:flex">
    <aside
        id="app-navigation"
        class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full border-r border-slate-200 bg-white transition lg:static lg:translate-x-0"
        :class="{ 'translate-x-0': navOpen }"
        aria-label="Primary navigation"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight">RankWatch</a>
                <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" x-on:click="navOpen = false" aria-label="Close navigation">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">
                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Workspace</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('dashboard') }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Overview</a>
                        <a href="{{ route('projects.index') }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('projects.index', 'projects.create') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Projects</a>
                    </div>
                </div>

                @isset($project)
                    <div>
                        <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Current project</p>
                        <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $project->name }}</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $project->domain }}</p>
                        </div>
                        <div class="mt-2 space-y-1">
                            <a href="{{ route('projects.show', $project) }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('projects.show') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Dashboard</a>
                            <a href="{{ route('keywords.index', $project) }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('keywords.*') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Keywords</a>
                            <a href="{{ route('projects.issues.index', $project) }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('projects.issues.*') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Issues</a>
                            <a href="{{ route('projects.report', $project) }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('projects.report') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Report</a>
                        </div>
                    </div>
                @endisset

                <div>
                    <p class="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Account</p>
                    <div class="mt-2 space-y-1">
                        <a href="{{ route('profile.edit') }}" x-on:click="navOpen = false" class="block rounded-lg px-3 py-2 font-medium {{ request()->routeIs('profile.edit') ? 'bg-slate-950 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}">Settings</a>
                    </div>
                </div>
            </nav>

            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200 p-3">
                @csrf
                <button class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950">Logout</button>
            </form>
        </div>
    </aside>

    <div class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden" x-show="navOpen" x-transition.opacity x-on:click="navOpen = false" x-cloak></div>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden" x-on:click="navOpen = true" x-bind:aria-expanded="navOpen.toString()" aria-controls="app-navigation" aria-label="Open navigation">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" /></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-500">{{ isset($project) ? $project->domain : 'SEO monitoring' }}</p>
                        <h1 class="truncate text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">{{ $heading ?? 'Dashboard' }}</h1>
                        @isset($subheading)
                            <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $subheading }}</p>
                        @endisset
                    </div>
                </div>
                @isset($actions)
                    <div class="shrink-0">{{ $actions }}</div>
                @endisset
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                @php($statusMessage = match (session('status')) {
                    'profile-updated' => 'Profile updated.',
                    'password-updated' => 'Password updated.',
                    'verification-link-sent' => 'Verification link sent.',
                    default => session('status'),
                })
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $statusMessage }}</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
