<x-app-layout>
    <x-slot:title>{{ $keyword->keyword }} - {{ $project->name }}</x-slot:title>
    <x-slot:heading>{{ $keyword->keyword }}</x-slot:heading>
    <x-slot:subheading>{{ $project->name }} - {{ $project->domain }}</x-slot:subheading>

    @php
        $latestRanking = $keyword->recentRankings->first();
        $change = $keyword->position_change;
    @endphp

    <x-slot:actions>
        <x-button :href="route('keywords.edit', [$project, $keyword])" variant="secondary">Edit Keyword</x-button>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-card>
            <p class="text-sm font-medium text-slate-500">Current position</p>
            <p class="mt-2 text-3xl font-bold">{{ $keyword->current_position ? '#'.$keyword->current_position : $keyword->current_position_label }}</p>
            <p class="mt-2 text-sm text-slate-500">{{ $latestRanking?->checked_at?->diffForHumans() ?? 'No ranking history yet' }}</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Previous position</p>
            <p class="mt-2 text-3xl font-bold">{{ $keyword->previous_position ? '#'.$keyword->previous_position : $keyword->previous_position_label }}</p>
            <p class="mt-2 text-sm text-slate-500">Only valid successful observations are compared.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Best position</p>
            <p class="mt-2 text-3xl font-bold">{{ $keyword->best_position ? '#'.$keyword->best_position : '-' }}</p>
            <p class="mt-2 text-sm text-slate-500">Best found ranking.</p>
        </x-card>
        <x-card>
            <p class="text-sm font-medium text-slate-500">Movement</p>
            <p class="mt-2 text-3xl font-bold">
                @if ($change > 0)
                    Improved {{ $change }}
                @elseif ($change < 0)
                    Dropped {{ abs($change) }}
                @else
                    -
                @endif
            </p>
            <p class="mt-2 text-sm text-slate-500">Lower ranking positions are better.</p>
        </x-card>
    </div>

    <x-card class="mt-6">
        <h2 class="text-lg font-semibold">Tracking settings</h2>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-slate-500">Project</dt><dd class="mt-1 font-medium text-slate-950">{{ $project->name }}</dd></div>
            <div><dt class="text-slate-500">Country</dt><dd class="mt-1 font-medium text-slate-950">{{ $keyword->country }}</dd></div>
            <div><dt class="text-slate-500">Device</dt><dd class="mt-1 font-medium text-slate-950">{{ ucfirst($keyword->device) }}</dd></div>
            <div><dt class="text-slate-500">Target URL</dt><dd class="mt-1 break-words font-medium text-slate-950">{{ $keyword->target_url ?: '-' }}</dd></div>
        </dl>
    </x-card>

    <x-card class="mt-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Ranking history</h2>
                <p class="mt-1 text-sm text-slate-500">Lower bars mean better positions. Provider failures do not create history rows.</p>
            </div>
        </div>

        @if ($chartRankings->isNotEmpty())
            <div class="mt-5 flex h-40 items-end gap-2 border-b border-slate-200 pb-2" aria-label="Recent found ranking positions">
                @foreach ($chartRankings as $ranking)
                    <div class="flex min-w-8 flex-1 flex-col items-center gap-2">
                        <div class="w-full rounded-t bg-slate-800" title="{{ $ranking->checked_at->format('M j, Y') }}: #{{ $ranking->position }}" style="height: {{ max(8, 100 - $ranking->position) }}%"></div>
                        <span class="text-xs text-slate-500">#{{ $ranking->position }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-5 rounded-lg border border-dashed border-slate-300 p-6 text-sm text-slate-600">No found ranking chart data yet. Ranking data appears after automatic checks find this domain.</div>
        @endif

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-[520px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr><th scope="col" class="py-3 pr-4">Checked time</th><th scope="col" class="px-3 py-3">Status</th><th scope="col" class="px-3 py-3">Position</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($historyRankings as $ranking)
                    <tr>
                        <td class="py-3 pr-4">{{ $ranking->checked_at->format('M j, Y H:i') }}</td>
                        <td class="px-3 py-3"><x-badge :tone="$ranking->status === \App\Models\KeywordRanking::STATUS_FOUND ? 'low' : 'slate'">{{ $ranking->status === \App\Models\KeywordRanking::STATUS_FOUND ? 'Found' : 'Not found' }}</x-badge></td>
                        <td class="px-3 py-3">{{ $ranking->position ? '#'.$ranking->position : 'Not found' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-8 text-center text-sm text-slate-600">No ranking history yet. Ranking data appears after automatic checks run.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $historyRankings->links() }}</div>
    </x-card>
</x-app-layout>
