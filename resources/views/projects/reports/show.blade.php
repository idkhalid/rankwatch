<x-app-layout>
    <x-slot:title>SEO Report - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Report</x-slot:heading>
    <x-slot:subheading>Current SEO and ranking summary for {{ $project->domain }}.</x-slot:subheading>

    @php
        $latestCrawl = $project->latestCompletedCrawl;
        $foundKeywords = $keywords->filter(fn ($keyword) => $keyword->current_position !== null)->count();
    @endphp

    <x-slot:actions>
        <button type="button" onclick="window.print()" class="no-print inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500">Print</button>
    </x-slot:actions>

    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            body { background: #fff !important; color: #0f172a !important; }
            main { max-width: none !important; padding: 0 !important; }
            section, table, .print-avoid-break { break-inside: avoid; page-break-inside: avoid; }
            .shadow-sm { box-shadow: none !important; }
        }
    </style>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 print-avoid-break">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Current report view</p>
                <h2 class="mt-2 text-2xl font-bold tracking-tight">{{ $project->name }}</h2>
                <p class="mt-1 break-words text-slate-600">{{ $project->url }}</p>
                <p class="mt-3 text-sm text-slate-500">Viewed {{ now()->format('M j, Y H:i') }}. Latest completed crawl: {{ $latestCrawl?->finished_at?->format('M j, Y H:i') ?? 'No completed crawl yet' }}.</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 text-center">
                <p class="text-sm font-medium text-slate-500">SEO score</p>
                <p class="mt-1 text-4xl font-bold">{{ $score }} / 100</p>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">Tracked keywords</p>
            <p class="mt-2 text-3xl font-bold">{{ $keywords->count() }}</p>
            <p class="mt-2 text-sm text-slate-500">Plan-bounded project keyword list.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Found rankings</p>
            <p class="mt-2 text-3xl font-bold">{{ $foundKeywords }}</p>
            <p class="mt-2 text-sm text-slate-500">Current found keyword observations.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Current issues</p>
            <p class="mt-2 text-3xl font-bold">{{ $issues->count() }}</p>
            <p class="mt-2 text-sm text-slate-500">Open issues from latest completed crawl.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Pages crawled</p>
            <p class="mt-2 text-3xl font-bold">{{ $latestCrawl?->pages_crawled ?? 0 }}</p>
            <p class="mt-2 text-sm text-slate-500">Failed and running crawls are excluded.</p>
        </x-card>
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40 print-avoid-break">
        <h2 class="text-lg font-semibold">Overview</h2>
        @if (! $latestCrawl)
            <p class="mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-600">No completed crawl yet. Technical SEO sections will populate after a successful crawl.</p>
        @else
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Crawl completed</dt><dd class="mt-1 font-semibold text-slate-950">{{ $latestCrawl->finished_at->format('M j, Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">Pages crawled</dt><dd class="mt-1 font-semibold text-slate-950">{{ $latestCrawl->pages_crawled }}</dd></div>
                <div><dt class="text-slate-500">Open issues</dt><dd class="mt-1 font-semibold text-slate-950">{{ $issues->count() }}</dd></div>
            </dl>
        @endif
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Keyword Rankings</h2>
                <p class="mt-1 text-sm text-slate-500">Current position, movement, and best found position.</p>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-[760px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th scope="col" class="py-3 pr-4">Keyword</th><th scope="col" class="px-3 py-3">Position</th><th scope="col" class="px-3 py-3">Change</th><th scope="col" class="px-3 py-3">Best</th><th scope="col" class="px-3 py-3">Country</th><th scope="col" class="px-3 py-3">Device</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($keywords as $keyword)
                    @php($change = $keyword->position_change)
                    <tr>
                        <td class="py-3 pr-4 font-medium text-slate-950">{{ $keyword->keyword }}</td>
                        <td class="px-3 py-3">{{ $keyword->current_position ? '#'.$keyword->current_position : $keyword->current_position_label }}</td>
                        <td class="px-3 py-3">
                            @if ($change > 0)
                                Improved {{ $change }}
                            @elseif ($change < 0)
                                Dropped {{ abs($change) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-3 py-3">{{ $keyword->best_position ? '#'.$keyword->best_position : '-' }}</td>
                        <td class="px-3 py-3">{{ $keyword->country }}</td>
                        <td class="px-3 py-3">{{ ucfirst($keyword->device) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-sm text-slate-600">No keywords tracked yet. Ranking data will appear after keywords are added and checked.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/40">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Technical SEO</h2>
                <p class="mt-1 text-sm text-slate-500">Current open issues from the latest completed crawl.</p>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach (\App\Models\SeoIssue::SEVERITIES as $level)
                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                        <x-badge :tone="$level">{{ ucfirst($level) }}</x-badge>
                        <p class="mt-2 text-xl font-bold">{{ (int) ($severityCounts[$level] ?? 0) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-5 divide-y divide-slate-200">
            @forelse ($issues as $issue)
                <div class="py-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge>
                            <p class="mt-2 font-semibold text-slate-950">{{ str_replace('_', ' ', ucfirst($issue->type)) }}</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $issue->message }}</p>
                        </div>
                        <p class="max-w-md break-words text-sm text-slate-500">{{ $issue->page_url }}</p>
                    </div>
                </div>
            @empty
                @if ($latestCrawl)
                    <p class="py-8 text-center text-sm text-slate-600">No current SEO issues detected in the latest completed crawl.</p>
                @else
                    <p class="py-8 text-center text-sm text-slate-600">No completed crawl yet.</p>
                @endif
            @endforelse
        </div>
    </section>
</x-app-layout>
