<x-app-layout>
    <x-slot:title>Issues - {{ $project->name }}</x-slot:title>
    <x-slot:heading>SEO Issues</x-slot:heading>
    <x-slot:subheading>Current issues from the latest completed crawl for {{ $project->domain }}.</x-slot:subheading>

    <x-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Latest completed crawl</p>
                <p class="mt-1 text-lg font-semibold text-slate-950">{{ $crawl?->finished_at?->format('M j, Y H:i') ?? 'No completed crawl yet' }}</p>
                <p class="mt-1 text-sm text-slate-500">Failed or running crawls are not used as current SEO state.</p>
            </div>
            <form method="GET" action="{{ route('projects.issues.index', $project) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div>
                    <label for="severity" class="text-sm font-medium text-slate-700">Severity</label>
                    <select id="severity" name="severity" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:w-44">
                        <option value="">All severities</option>
                        @foreach (\App\Models\SeoIssue::SEVERITIES as $level)
                            <option value="{{ $level }}" @selected($severity === $level)>{{ ucfirst($level) }} ({{ (int) ($severityCounts[$level] ?? 0) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit">Filter</x-button>
                    @if ($severity)
                        <x-button :href="route('projects.issues.index', $project)" variant="secondary">Clear</x-button>
                    @endif
                </div>
            </form>
        </div>
    </x-card>

    <div class="mt-6 grid gap-3 sm:grid-cols-4">
        @foreach (\App\Models\SeoIssue::SEVERITIES as $level)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/40">
                <x-badge :tone="$level">{{ ucfirst($level) }}</x-badge>
                <p class="mt-3 text-2xl font-bold">{{ (int) ($severityCounts[$level] ?? 0) }}</p>
            </div>
        @endforeach
    </div>

    <x-card class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-[820px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="py-3 pr-4">Issue</th>
                        <th scope="col" class="px-3 py-3">Severity</th>
                        <th scope="col" class="px-3 py-3">Affected page</th>
                        <th scope="col" class="px-3 py-3">Current crawl</th>
                        <th scope="col" class="py-3 pl-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($issues as $issue)
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="py-4 pr-4">
                            <p class="font-semibold text-slate-950">{{ str_replace('_', ' ', ucfirst($issue->type)) }}</p>
                            <p class="mt-1 text-slate-600">{{ $issue->message }}</p>
                        </td>
                        <td class="px-3 py-4"><x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge></td>
                        <td class="px-3 py-4"><span class="block max-w-sm break-words text-slate-700">{{ $issue->page_url }}</span></td>
                        <td class="px-3 py-4 text-slate-600">{{ $crawl?->finished_at?->diffForHumans() ?? '-' }}</td>
                        <td class="py-4 pl-3 text-right">
                            <form method="POST" action="{{ route('projects.issues.update', [$project, $issue]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="font-medium text-slate-700 hover:text-slate-950">Mark resolved</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center">
                            @if (! $crawl)
                                <p class="font-semibold text-slate-900">No completed crawl yet.</p>
                                <p class="mt-1 text-sm text-slate-500">Run a crawl to collect current SEO issues for this project.</p>
                                <form method="POST" action="{{ route('projects.crawl', $project) }}" class="mt-4">
                                    @csrf
                                    <x-button type="submit">Run Crawl</x-button>
                                </form>
                            @elseif ($severity)
                                <p class="font-semibold text-slate-900">No {{ $severity }} issues in the latest completed crawl.</p>
                                <p class="mt-1 text-sm text-slate-500">Clear the filter to review all current issues.</p>
                            @else
                                <p class="font-semibold text-slate-900">No current SEO issues detected in the latest completed crawl.</p>
                                <p class="mt-1 text-sm text-slate-500">This is a current crawl result, not a guarantee that every optimization opportunity is complete.</p>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $issues->links() }}</div>
    </x-card>
</x-app-layout>
