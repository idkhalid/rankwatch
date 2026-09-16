<x-app-layout>
    <x-slot:title>{{ $project->name }} - RankWatch</x-slot:title>
    <x-slot:heading>{{ $project->name }}</x-slot:heading>
    @php
        $keywords = $project->keywords;
        $keywordLimit = auth()->user()->planLimit('keywords_per_project');
    @endphp
    @if ($keywords->count() >= $keywordLimit && auth()->user()->isFree())
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This website has {{ $keywords->count() }} / {{ $keywordLimit }} keywords. Upgrade to Pro for more keywords per website.</div>
    @endif
    <div class="mb-5 flex flex-wrap gap-3">
        <x-button :href="route('keywords.create', $project)">Add keyword</x-button>
        <form method="POST" action="{{ route('projects.crawl', $project) }}">@csrf<x-button type="submit" variant="secondary">Run crawl</x-button></form>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-card><p class="text-sm text-slate-500">SEO Score</p><p class="mt-2 text-3xl font-bold">{{ $score }} / 100</p></x-card>
        <x-card><p class="text-sm text-slate-500">Tracked Keywords</p><p class="mt-2 text-3xl font-bold">{{ $keywords->count() }} / {{ $keywordLimit }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Average Position</p><p class="mt-2 text-3xl font-bold">{{ round($keywords->pluck('current_position')->filter()->avg() ?? 0, 1) ?: '-' }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Last Crawl</p><p class="mt-2 text-xl font-bold">{{ $project->latestCompletedCrawl?->finished_at?->diffForHumans() ?? 'Never' }}</p></x-card>
    </div>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card>
            <h2 class="font-semibold">Recent issues</h2>
            <div class="mt-3 divide-y divide-slate-200">
                @forelse ($issues as $issue)
                    <div class="py-3"><x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge><p class="mt-2 text-sm">{{ $issue->message }}</p></div>
                @empty
                    <p class="py-4 text-sm text-slate-500">No open issues.</p>
                @endforelse
            </div>
        </x-card>
        <x-card>
            <h2 class="font-semibold">Organic visibility trend</h2>
            <div class="mt-4 flex h-32 items-end gap-2">
                @foreach ($keywords->take(12) as $keyword)
                    <div class="w-full rounded-t bg-emerald-600" style="height: {{ max(8, 100 - ($keyword->current_position ?? 80)) }}%"></div>
                @endforeach
            </div>
        </x-card>
    </div>
</x-app-layout>
