<x-app-layout>
    <x-slot:title>New Project - RankWatch</x-slot:title>
    <x-slot:heading>New Project</x-slot:heading>
    <x-slot:subheading>Add a website root URL for monitoring.</x-slot:subheading>

    @php($atLimit = $projectCount >= $projectLimit)

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <x-card>
            <form method="POST" action="{{ route('projects.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Project name</label>
                    <x-input id="name" name="name" value="{{ old('name') }}" required class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Use a recognizable website or client name.</p>
                </div>
                <div>
                    <label for="url" class="text-sm font-medium text-slate-700">Website URL</label>
                    <x-input id="url" name="url" type="url" value="{{ old('url') }}" placeholder="https://example.com" required class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Use the public website root. Paths, query strings, fragments, and credentials are not supported.</p>
                </div>
                <div>
                    <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description') }}</textarea>
                </div>
                <x-input-error :messages="$errors->all()" />
                <div class="flex flex-wrap items-center gap-3">
                    @if ($atLimit)
                        <button type="button" disabled class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-500">Limit reached</button>
                    @else
                        <x-button type="submit">Create Project</x-button>
                    @endif
                    <x-button :href="route('projects.index')" variant="secondary">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <p class="text-sm font-medium text-slate-500">Project usage</p>
            <p class="mt-2 text-2xl font-bold">{{ $projectCount }} / {{ $projectLimit }} websites</p>
            <p class="mt-2 text-sm text-slate-600">{{ ucfirst(auth()->user()->plan) }}{{ auth()->user()->isPro() ? ' - Coming soon' : '' }} workspace limit.</p>
            @if ($atLimit)
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">Your current plan limit is reached. Remove a project before adding another.</p>
            @endif
        </x-card>
    </div>
</x-app-layout>
