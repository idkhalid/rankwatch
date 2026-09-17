<x-app-layout>
    <x-slot:title>Edit Keyword - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Edit Keyword</x-slot:heading>
    <x-slot:subheading>Update tracking settings for {{ $project->domain }}.</x-slot:subheading>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <x-card>
            <form method="POST" action="{{ route('keywords.update', [$project, $keyword]) }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <div>
                    <label for="keyword" class="text-sm font-medium text-slate-700">Keyword</label>
                    <x-input id="keyword" name="keyword" value="{{ old('keyword', $keyword->keyword) }}" required class="mt-1" />
                </div>
                <div>
                    <label for="target_url" class="text-sm font-medium text-slate-700">Target URL</label>
                    <x-input id="target_url" name="target_url" type="url" value="{{ old('target_url', $keyword->target_url) }}" class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Optional page you expect to rank for this keyword.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="country" class="text-sm font-medium text-slate-700">Country</label>
                        <x-input id="country" name="country" value="{{ old('country', $keyword->country) }}" required class="mt-1" />
                    </div>
                    <div>
                        <label for="device" class="text-sm font-medium text-slate-700">Device</label>
                        <select id="device" name="device" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="desktop" @selected(old('device', $keyword->device) === 'desktop')>Desktop</option>
                            <option value="mobile" @selected(old('device', $keyword->device) === 'mobile')>Mobile</option>
                        </select>
                    </div>
                </div>
                <x-input-error :messages="$errors->all()" />
                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="submit">Save Keyword</x-button>
                    <x-button :href="route('keywords.index', $project)" variant="secondary">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <p class="text-sm font-medium text-slate-500">Keyword usage</p>
                <p class="mt-2 text-2xl font-bold">{{ $keywordCount }} / {{ $keywordLimit }}</p>
                <p class="mt-2 text-sm text-slate-600">Editing does not change plan usage.</p>
            </x-card>
            <x-card>
                <h2 class="font-semibold text-slate-950">Delete keyword</h2>
                <p class="mt-2 text-sm text-slate-600">Deleting removes this keyword and its ranking history.</p>
                <form method="POST" action="{{ route('keywords.destroy', [$project, $keyword]) }}" class="mt-4" onsubmit="return confirm('Delete this keyword and its ranking history?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">Delete Keyword</button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
