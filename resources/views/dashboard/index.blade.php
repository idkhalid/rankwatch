<x-app-layout>
    <x-slot:title>RankWatch Dashboard</x-slot:title>
    <x-slot:heading>Overview</x-slot:heading>
    <x-slot:subheading>Current monitoring health across your websites, keywords, crawls, and open SEO issues.</x-slot:subheading>

    @php
        $user = auth()->user();
        $projectLimit = $user->planLimit('projects');
        $keywordLimit = $user->planLimit('keywords_per_project');
        $crawlLimit = $user->planLimit('crawl_pages');
        $frequency = ucfirst((string) $user->planLimit('crawl_frequency'));
        $primaryProject = $projects->first();
        $scoreTone = $score >= 90 ? 'text-emerald-700' : ($score >= 70 ? 'text-amber-700' : 'text-red-700');
    @endphp

    <x-slot:actions>
        @if ($primaryProject)
            <form method="POST" action="{{ route('projects.crawl', $primaryProject) }}">
                @csrf
                <x-button type="submit">Run Crawl</x-button>
            </form>
        @else
            <x-button :href="route('projects.create')">Create Project</x-button>
        @endif
    </x-slot:actions>

    @if (! $primaryProject)
        <section class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm shadow-slate-200/50">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">No websites yet</p>
            <h2 class="mt-3 text-2xl font-bold tracking-tight">Create your first project to start monitoring.</h2>
            <p class="mt-3 max-w-2xl text-slate-600">Add a website root, then add keywords or queue a manual crawl to start building your SEO baseline.</p>
            <x-button :href="route('projects.create')" class="mt-6">Create Project</x-button>
        </section>
    @else
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recent project</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight">{{ $primaryProject->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $primaryProject->domain }}</p>
                    <p class="mt-3 text-sm text-slate-600">Latest completed crawl: {{ $lastCrawl?->finished_at?->diffForHumans() ?? 'No completed crawl yet' }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-button :href="route('projects.show', $primaryProject)" variant="secondary">Open Dashboard</x-button>
                    <x-button :href="route('keywords.index', $primaryProject)" variant="subtle">View Keywords</x-button>
                </div>
            </div>
        </section>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">SEO Score</p>
            <p class="mt-2 text-3xl font-bold {{ $scoreTone }}">{{ $score }} / 100</p>
            <p class="mt-2 text-sm text-slate-500">Based on open issues from latest completed crawls.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Keywords Tracked</p>
            <p class="mt-2 text-3xl font-bold">{{ $keywordCount }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $projectCount }} {{ $projectCount === 1 ? 'website' : 'websites' }} monitored.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Average Position</p>
            <p class="mt-2 text-3xl font-bold">{{ $averagePosition ?: '-' }}</p>
            <p class="mt-2 text-sm text-slate-500">Current found keyword positions only.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Open Issues</p>
            <p class="mt-2 text-3xl font-bold">{{ $issueCount }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $issueCount === 0 ? 'No current issues found.' : 'Review current crawl findings.' }}</p>
        </x-card>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <x-card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Issue overview</h2>
                        <p class="mt-1 text-sm text-slate-500">Open issues grouped by current severity.</p>
                    </div>
                    @if ($primaryProject)
                        <x-button :href="route('projects.issues.index', $primaryProject)" variant="secondary">View Issues</x-button>
                    @endif
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-4">
                    @foreach (\App\Models\SeoIssue::SEVERITIES as $severity)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <x-badge :tone="$severity">{{ ucfirst($severity) }}</x-badge>
                            <p class="mt-3 text-2xl font-bold">{{ (int) ($issueCounts[$severity] ?? 0) }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Projects</h2>
                        <p class="mt-1 text-sm text-slate-500">Bounded overview of your most recent websites.</p>
                    </div>
                    <x-button :href="route('projects.index')" variant="secondary">All Projects</x-button>
                </div>
                <div class="mt-5 divide-y divide-slate-200">
                    @forelse ($projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="block py-4 hover:bg-slate-50">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ $project->name }}</p>
                                    <p class="truncate text-sm text-slate-500">{{ $project->domain }}</p>
                                </div>
                                <div class="text-sm font-medium text-slate-600">{{ $project->keywords_count }} {{ $project->keywords_count === 1 ? 'keyword' : 'keywords' }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-600">
                            No projects yet. Create a project to start tracking rankings and SEO issues.
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card>
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold">{{ ucfirst($user->plan) }} Plan</p>
                        @if ($user->isPro())
                            <p class="mt-1 text-sm text-slate-500">Pro - Coming soon</p>
                        @else
                            <p class="mt-1 text-sm text-slate-500">Free workspace limits</p>
                        @endif
                    </div>
                    <x-button :href="route('marketing.pricing')" variant="secondary">View plans</x-button>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Websites</dt><dd class="font-semibold">{{ $projectCount }} / {{ $projectLimit }}</dd></div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-slate-950" style="width: {{ min(100, $projectLimit ? ($projectCount / $projectLimit) * 100 : 0) }}%"></div></div>
                    </div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Keywords</dt><dd class="font-semibold">{{ $keywordCount }} / {{ $keywordLimit }} per website</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Crawl limit</dt><dd class="font-semibold">Up to {{ $crawlLimit }} pages</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Automatic monitoring</dt><dd class="font-semibold">{{ $frequency }}</dd></div>
                </dl>
                @if ($projectCount >= $projectLimit && $user->isFree())
                    <p class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Your Free plan supports 1 website. Upgrade to Pro to monitor additional websites.</p>
                @endif
            </x-card>

            <x-card>
                <h2 class="text-lg font-semibold">Ranking overview</h2>
                @if ($keywordCount > 0)
                    <p class="mt-2 text-sm text-slate-600">Average position is calculated from current found keyword observations. Keywords with no history or not-found status are excluded.</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">Add keywords to see current ranking status here.</p>
                    @if ($primaryProject)
                        <x-button :href="route('keywords.create', $primaryProject)" class="mt-4">Add Keyword</x-button>
                    @endif
                @endif
            </x-card>
        </aside>
    </section>
</x-app-layout>
