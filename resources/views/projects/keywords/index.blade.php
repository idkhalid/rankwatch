<x-app-layout>
    <x-slot:title>Keywords - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Keywords</x-slot:heading>
    <div class="mb-5 flex justify-end"><x-button :href="route('keywords.create', $project)">Add keyword</x-button></div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-slate-500"><tr><th class="py-2">Keyword</th><th>Current</th><th>Previous</th><th>Best</th><th>Change</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($keywords as $keyword)
                    <tr>
                        <td class="py-3"><a href="{{ route('keywords.show', [$project, $keyword]) }}" class="font-medium hover:underline">{{ $keyword->keyword }}</a><p class="text-slate-500">{{ $keyword->country }} / {{ $keyword->device }}</p></td>
                        <td>{{ $keyword->current_position_label }}</td>
                        <td>{{ $keyword->previous_position_label }}</td>
                        <td>{{ $keyword->best_position ?? '-' }}</td>
                        <td class="{{ ($keyword->position_change ?? 0) >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $keyword->position_change ?? '-' }}</td>
                        <td class="text-right"><a href="{{ route('keywords.edit', [$project, $keyword]) }}" class="text-slate-600 hover:text-slate-950">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-slate-500">No keywords yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $keywords->links() }}</div>
    </x-card>
</x-app-layout>
