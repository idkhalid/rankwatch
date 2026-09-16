<x-app-layout>
    <x-slot:title>RankWatch Dashboard</x-slot:title>
    <x-slot:heading>Dashboard</x-slot:heading>
    @php
        $user = auth()->user();
        $projectLimit = $user->planLimit('projects');
        $keywordLimit = $user->planLimit('keywords_per_project');
    @endphp
    <x-card class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold">{{ ucfirst($user->plan) }} Plan</p>
                <p class="mt-1 text-sm text-slate-500">Websites {{ $projectCount }} / {{ $projectLimit }} | Keywords {{ $keywordCount }} / {{ $keywordLimit }} per website | Up to {{ $user->planLimit('crawl_pages') }} pages/crawl</p>
            </div>
            <x-button :href="route('marketing.pricing')" variant="secondary">View plans</x-button>
        </div>
        @if ($projectCount >= $projectLimit && $user->isFree())
            <p class="mt-3 text-sm text-slate-600">Your Free plan supports 1 website. Upgrade to Pro to monitor additional websites.</p>
        @endif
    </x-card>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card><p class="text-sm text-slate-500">SEO Score</p><p class="mt-2 text-3xl font-bold">{{ $score }} / 100</p></x-card>
        <x-card><p class="text-sm text-slate-500">Keywords</p><p class="mt-2 text-3xl font-bold">{{ $keywordCount }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Average Position</p><p class="mt-2 text-3xl font-bold">{{ $averagePosition ?: '-' }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">SEO Issues</p><p class="mt-2 text-3xl font-bold">{{ $issueCount }}</p></x-card>
    </div>
    <x-card class="mt-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Projects</h2>
            <x-button :href="route('projects.create')">New project</x-button>
        </div>
        <div class="mt-4 divide-y divide-slate-200">
            @forelse ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="block py-4 hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="font-medium">{{ $project->name }}</p><p class="text-sm text-slate-500">{{ $project->domain }}</p></div>
                        <div class="text-sm text-slate-500">{{ $project->keywords_count }} keywords</div>
                    </div>
                </a>
            @empty
                <p class="py-6 text-slate-500">Create your first project to start monitoring.</p>
            @endforelse
        </div>
    </x-card>
</x-app-layout>
