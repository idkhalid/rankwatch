<x-app-layout>
    <x-slot:title>{{ $keyword->keyword }} - RankWatch</x-slot:title>
    <x-slot:heading>{{ $keyword->keyword }}</x-slot:heading>
    <div class="grid gap-4 sm:grid-cols-4">
        <x-card><p class="text-sm text-slate-500">Current</p><p class="mt-2 text-3xl font-bold">{{ $keyword->current_position_label }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Previous</p><p class="mt-2 text-3xl font-bold">{{ $keyword->previous_position_label }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Best</p><p class="mt-2 text-3xl font-bold">{{ $keyword->best_position ?? '-' }}</p></x-card>
        <x-card><p class="text-sm text-slate-500">Change</p><p class="mt-2 text-3xl font-bold">{{ $keyword->position_change ?? '-' }}</p></x-card>
    </div>
    <x-card class="mt-6">
        <h2 class="font-semibold">Ranking history</h2>
        <div class="mt-4 flex h-36 items-end gap-2">
            @foreach ($keyword->rankings->where('status', \App\Models\KeywordRanking::STATUS_FOUND)->sortBy('checked_at')->take(-30) as $ranking)
                <div class="w-full rounded-t bg-slate-800" title="{{ $ranking->checked_at->toDateString() }}: {{ $ranking->position_label }}" style="height: {{ max(6, 100 - $ranking->position) }}%"></div>
            @endforeach
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-slate-500"><tr><th class="py-2">Checked</th><th>Position</th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @foreach ($keyword->rankings->sort(fn ($a, $b) => [$b->checked_at?->getTimestamp() ?? 0, $b->id] <=> [$a->checked_at?->getTimestamp() ?? 0, $a->id]) as $ranking)
                    <tr><td class="py-2">{{ $ranking->checked_at->format('M j, Y H:i') }}</td><td>{{ $ranking->position_label }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
