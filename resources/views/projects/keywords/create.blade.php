<x-app-layout>
    <x-slot:title>Add Keyword - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Add Keyword</x-slot:heading>
    <x-slot:subheading>Track a search phrase for {{ $project->domain }}.</x-slot:subheading>

    @php($atLimit = $keywordCount >= $keywordLimit)

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <x-card>
            <form method="POST" action="{{ route('keywords.store', $project) }}" class="space-y-5">
                @csrf
                <div>
                    <label for="keyword" class="text-sm font-medium text-slate-700">Keyword</label>
                    <x-input id="keyword" name="keyword" value="{{ old('keyword') }}" required class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Use the exact phrase you want to monitor.</p>
                </div>
                <div>
                    <label for="target_url" class="text-sm font-medium text-slate-700">Target URL</label>
                    <x-input id="target_url" name="target_url" type="url" value="{{ old('target_url') }}" placeholder="https://{{ $project->domain }}/page" class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Optional page you expect to rank for this keyword.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="country" class="text-sm font-medium text-slate-700">Country</label>
                        <x-input id="country" name="country" value="{{ old('country', 'Indonesia') }}" required class="mt-1" />
                    </div>
                    <div>
                        <label for="device" class="text-sm font-medium text-slate-700">Device</label>
                        <select id="device" name="device" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="desktop" @selected(old('device', 'desktop') === 'desktop')>Desktop</option>
                            <option value="mobile" @selected(old('device') === 'mobile')>Mobile</option>
                        </select>
                    </div>
                </div>
                <x-input-error :messages="$errors->all()" />
                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="submit" @disabled($atLimit)>{{ $atLimit ? 'Limit reached' : 'Add Keyword' }}</x-button>
                    <x-button :href="route('keywords.index', $project)" variant="secondary">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <p class="text-sm font-medium text-slate-500">Keyword usage</p>
            <p class="mt-2 text-2xl font-bold">{{ $keywordCount }} / {{ $keywordLimit }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ ucfirst(auth()->user()->plan) }}{{ auth()->user()->isPro() ? ' - Coming soon' : '' }} limit for this website.</p>
            @if ($atLimit)
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">This website is at its keyword limit. Remove a keyword before adding another.</p>
            @endif
        </x-card>
    </div>
</x-app-layout>
