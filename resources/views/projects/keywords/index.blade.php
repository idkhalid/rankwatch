<x-app-layout>
    <x-slot:title>Keywords - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Keywords</x-slot:heading>
    <x-slot:subheading>Track search visibility for {{ $project->domain }}.</x-slot:subheading>

    @php
        $atLimit = $keywordCount >= $keywordLimit;
        $hasFilters = $search !== '' || $country !== '' || $device !== '';
    @endphp

    <x-slot:actions>
        @if ($atLimit)
            <span class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">Limit reached</span>
        @else
            <x-button :href="route('keywords.create', $project)">Add Keyword</x-button>
        @endif
    </x-slot:actions>

    <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
        <x-card>
            <form method="GET" action="{{ route('keywords.index', $project) }}" class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px_160px_auto] md:items-end">
                <div>
                    <label for="search" class="text-sm font-medium text-slate-700">Search keywords</label>
                    <x-input id="search" name="search" value="{{ $search }}" placeholder="Keyword text" class="mt-1" />
                </div>
                <div>
                    <label for="country" class="text-sm font-medium text-slate-700">Country</label>
                    <select id="country" name="country" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">All countries</option>
                        @foreach ($countries as $option)
                            <option value="{{ $option }}" @selected($country === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="device" class="text-sm font-medium text-slate-700">Device</label>
                    <select id="device" name="device" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        <option value="">All devices</option>
                        @foreach ($devices as $option)
                            <option value="{{ $option }}" @selected($device === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <x-button type="submit">Filter</x-button>
                    @if ($hasFilters)
                        <x-button :href="route('keywords.index', $project)" variant="secondary">Clear</x-button>
                    @endif
                </div>
            </form>
        </x-card>

        <x-card>
            <p class="text-sm font-medium text-slate-500">Keyword usage</p>
            <p class="mt-2 text-2xl font-bold">{{ $keywordCount }} / {{ $keywordLimit }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ ucfirst(auth()->user()->plan) }}{{ auth()->user()->isPro() ? ' - Coming soon' : '' }}</p>
            @if ($atLimit)
                <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">This website is at its keyword limit. Backend validation still enforces the limit.</p>
            @endif
        </x-card>
    </div>

    <x-card class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-[860px] w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="py-3 pr-4">Keyword</th>
                        <th scope="col" class="px-3 py-3">Position</th>
                        <th scope="col" class="px-3 py-3">Change</th>
                        <th scope="col" class="px-3 py-3">Best</th>
                        <th scope="col" class="px-3 py-3">Country</th>
                        <th scope="col" class="px-3 py-3">Device</th>
                        <th scope="col" class="px-3 py-3">Last checked</th>
                        <th scope="col" class="py-3 pl-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($keywords as $keyword)
                    @php
                        $latestRanking = $keyword->recentRankings->first();
                        $change = $keyword->position_change;
                    @endphp
                    <tr class="align-top hover:bg-slate-50/70">
                        <td class="py-4 pr-4">
                            <a href="{{ route('keywords.show', [$project, $keyword]) }}" class="font-semibold text-slate-950 hover:underline">{{ $keyword->keyword }}</a>
                            @if ($keyword->target_url)
                                <p class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $keyword->target_url }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-4">
                            @if ($keyword->current_position_label === 'Not found')
                                <x-badge tone="slate">Not found</x-badge>
                            @elseif ($keyword->current_position)
                                <span class="font-semibold">#{{ $keyword->current_position }}</span>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4">
                            @if ($change > 0)
                                <span class="font-medium text-emerald-700">Improved {{ $change }}</span>
                            @elseif ($change < 0)
                                <span class="font-medium text-red-700">Dropped {{ abs($change) }}</span>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-4">{{ $keyword->best_position ? '#'.$keyword->best_position : '-' }}</td>
                        <td class="px-3 py-4">{{ $keyword->country }}</td>
                        <td class="px-3 py-4">{{ ucfirst($keyword->device) }}</td>
                        <td class="px-3 py-4">{{ $latestRanking?->checked_at?->diffForHumans() ?? '-' }}</td>
                        <td class="py-4 pl-3 text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                <a href="{{ route('keywords.show', [$project, $keyword]) }}" class="font-medium text-slate-700 hover:text-slate-950">View</a>
                                <a href="{{ route('keywords.edit', [$project, $keyword]) }}" class="font-medium text-slate-700 hover:text-slate-950">Edit</a>
                                <form method="POST" action="{{ route('keywords.destroy', [$project, $keyword]) }}" onsubmit="return confirm('Delete this keyword?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:text-red-900">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center">
                            @if ($hasFilters)
                                <p class="font-semibold text-slate-900">No keywords match these filters.</p>
                                <p class="mt-1 text-sm text-slate-500">Clear the filters to return to the full keyword list.</p>
                            @else
                                <p class="font-semibold text-slate-900">No keywords yet.</p>
                                <p class="mt-1 text-sm text-slate-500">Add a keyword to start collecting ranking history after automatic checks run.</p>
                                @unless ($atLimit)
                                    <x-button :href="route('keywords.create', $project)" class="mt-4">Add Keyword</x-button>
                                @endunless
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $keywords->links() }}</div>
    </x-card>
</x-app-layout>
