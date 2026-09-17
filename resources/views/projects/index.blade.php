<x-app-layout>
    <x-slot:title>Projects - RankWatch</x-slot:title>
    <x-slot:heading>Projects</x-slot:heading>
    <x-slot:subheading>Monitored websites, current SEO state, crawl recency, and plan capacity.</x-slot:subheading>

    @php($atLimit = $projectCount >= $projectLimit)

    <x-slot:actions>
        @if ($atLimit)
            <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">Limit reached</span>
        @else
            <x-button :href="route('projects.create')">Add Project</x-button>
        @endif
    </x-slot:actions>

    <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
        <x-card>
            <h2 class="text-lg font-semibold">Websites</h2>
            <p class="mt-1 text-sm text-slate-500">Open a project to review rankings, current issues, and report output.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Project usage</p>
            <p class="mt-2 text-2xl font-bold">{{ $projectCount }} / {{ $projectLimit }} websites</p>
            <p class="mt-1 text-sm text-slate-500">{{ ucfirst(auth()->user()->plan) }}{{ auth()->user()->isPro() ? ' - Coming soon' : '' }}</p>
            @if ($atLimit)
                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Your current plan limit is reached. Backend validation still enforces this limit.</p>
            @endif
        </x-card>
    </div>

    <x-card class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-[860px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="py-3 pr-4">Website</th>
                        <th scope="col" class="px-3 py-3">SEO score</th>
                        <th scope="col" class="px-3 py-3">Keywords</th>
                        <th scope="col" class="px-3 py-3">Current issues</th>
                        <th scope="col" class="px-3 py-3">Latest completed crawl</th>
                        <th scope="col" class="py-3 pl-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($projects as $project)
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="py-4 pr-4">
                            <a class="font-semibold text-slate-950 hover:underline" href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                            <p class="mt-1 max-w-sm break-words text-sm text-slate-500">{{ $project->domain }}</p>
                        </td>
                        <td class="px-3 py-4"><span class="font-semibold">{{ $project->seo_score }} / 100</span></td>
                        <td class="px-3 py-4">{{ $project->keywords_count }}</td>
                        <td class="px-3 py-4">{{ $project->current_issue_count }}</td>
                        <td class="px-3 py-4">{{ $project->latestCompletedCrawl?->finished_at?->diffForHumans() ?? 'No completed crawl yet' }}</td>
                        <td class="py-4 pl-3 text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                <a class="font-medium text-slate-700 hover:text-slate-950" href="{{ route('projects.show', $project) }}">Open</a>
                                <a class="font-medium text-slate-700 hover:text-slate-950" href="{{ route('projects.edit', $project) }}">Edit</a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all related monitoring data?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center">
                            <p class="font-semibold text-slate-900">No websites yet.</p>
                            <p class="mt-1 text-sm text-slate-500">Add a website root URL to start monitoring rankings and SEO issues.</p>
                            @unless ($atLimit)
                                <x-button :href="route('projects.create')" class="mt-4">Add Project</x-button>
                            @endunless
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $projects->links() }}</div>
    </x-card>
</x-app-layout>
