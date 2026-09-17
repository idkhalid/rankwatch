<x-app-layout>
    <x-slot:title>{{ $project->name }} - RankWatch</x-slot:title>
    <x-slot:heading>{{ $project->name }}</x-slot:heading>
    <x-slot:subheading>{{ $project->domain }}</x-slot:subheading>

    @php
        $keywordLimit = auth()->user()->planLimit('keywords_per_project');
        $keywordCount = $keywords->count();
        $averagePosition = round($keywords->pluck('current_position')->filter()->avg() ?? 0, 1) ?: '-';
        $atKeywordLimit = $keywordCount >= $keywordLimit;
    @endphp

    <x-slot:actions>
        <div class="flex flex-wrap justify-end gap-2">
            <x-button :href="route('keywords.create', $project)" variant="secondary">Add Keyword</x-button>
            <form method="POST" action="{{ route('projects.crawl', $project) }}">
                @csrf
                <x-button type="submit">Run Crawl</x-button>
            </form>
        </div>
    </x-slot:actions>

    @if ($atKeywordLimit && auth()->user()->isFree())
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This website has {{ $keywordCount }} / {{ $keywordLimit }} keywords. Pro - Coming soon.</div>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">SEO Score</p>
            <p class="mt-2 text-3xl font-bold">{{ $score }} / 100</p>
            <p class="mt-2 text-sm text-slate-500">Latest completed crawl only.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Tracked Keywords</p>
            <p class="mt-2 text-3xl font-bold">{{ $keywordCount }} / {{ $keywordLimit }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ ucfirst(auth()->user()->plan) }}{{ auth()->user()->isPro() ? ' - Coming soon' : '' }}</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Average Position</p>
            <p class="mt-2 text-3xl font-bold">{{ $averagePosition }}</p>
            <p class="mt-2 text-sm text-slate-500">Current found rankings only.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Current Issues</p>
            <p class="mt-2 text-3xl font-bold">{{ $issueCount }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $latestCrawl?->finished_at?->diffForHumans() ?? 'No completed crawl yet' }}</p>
        </x-card>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-6">
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Current SEO issues</h2>
                        <p class="mt-1 text-sm text-slate-500">Open issues from the latest completed crawl.</p>
                    </div>
                    <x-button :href="route('projects.issues.index', $project)" variant="secondary">View Issues</x-button>
                </div>
                <div class="mt-5 divide-y divide-slate-200">
                    @forelse ($issues as $issue)
                        <div class="py-4">
                            <x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge>
                            <p class="mt-2 font-medium text-slate-950">{{ $issue->message }}</p>
                            <p class="mt-1 break-words text-sm text-slate-500">{{ $issue->page_url }}</p>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-600">
                            {{ $latestCrawl ? 'No current SEO issues detected in the latest completed crawl.' : 'No completed crawl yet. Run a crawl to collect current SEO issues.' }}
                        </div>
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Keyword overview</h2>
                        <p class="mt-1 text-sm text-slate-500">Most recent tracked keywords for this website.</p>
                    </div>
                    <x-button :href="route('keywords.index', $project)" variant="secondary">View Keywords</x-button>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-[560px] w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr><th scope="col" class="py-3 pr-4">Keyword</th><th scope="col" class="px-3 py-3">Position</th><th scope="col" class="px-3 py-3">Device</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($keywords->take(8) as $keyword)
                                <tr>
                                    <td class="py-3 pr-4"><a href="{{ route('keywords.show', [$project, $keyword]) }}" class="font-medium text-slate-950 hover:underline">{{ $keyword->keyword }}</a></td>
                                    <td class="px-3 py-3">{{ $keyword->current_position ? '#'.$keyword->current_position : $keyword->current_position_label }}</td>
                                    <td class="px-3 py-3">{{ ucfirst($keyword->device) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-sm text-slate-600">No keywords yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <aside class="min-w-0 space-y-6">
            <x-card>
                <h2 class="text-lg font-semibold">Project links</h2>
                <div class="mt-4 grid gap-2">
                    <x-button :href="route('keywords.index', $project)" variant="secondary">Keywords</x-button>
                    <x-button :href="route('projects.issues.index', $project)" variant="secondary">Issues</x-button>
                    <x-button :href="route('projects.report', $project)" variant="secondary">Report</x-button>
                    <x-button :href="route('projects.edit', $project)" variant="subtle">Edit Project</x-button>
                </div>
            </x-card>
            <x-card>
                <h2 class="text-lg font-semibold">Crawl context</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Latest completed crawl</dt><dd class="mt-1 font-medium text-slate-950">{{ $latestCrawl?->finished_at?->format('M j, Y H:i') ?? 'No completed crawl yet' }}</dd></div>
                    <div><dt class="text-slate-500">Pages crawled</dt><dd class="mt-1 font-medium text-slate-950">{{ $latestCrawl?->pages_crawled ?? 0 }}</dd></div>
                    <div><dt class="text-slate-500">Monitoring cadence</dt><dd class="mt-1 font-medium text-slate-950">{{ ucfirst((string) auth()->user()->planLimit('crawl_frequency')) }}</dd></div>
                </dl>
                <p class="mt-4 text-sm text-slate-500">Run Crawl queues a crawl job; results appear after the job completes.</p>
            </x-card>
        </aside>
    </section>
</x-app-layout>
