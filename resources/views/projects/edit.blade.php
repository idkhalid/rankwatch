<x-app-layout>
    <x-slot:title>Edit Project - {{ $project->name }}</x-slot:title>
    <x-slot:heading>Edit Project</x-slot:heading>
    <x-slot:subheading>Update website details for {{ $project->domain }}.</x-slot:subheading>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <x-card>
            <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-5">
                @csrf
                @method('PATCH')
                <div>
                    <label for="name" class="text-sm font-medium text-slate-700">Project name</label>
                    <x-input id="name" name="name" value="{{ old('name', $project->name) }}" required class="mt-1" />
                </div>
                <div>
                    <label for="url" class="text-sm font-medium text-slate-700">Website URL</label>
                    <x-input id="url" name="url" type="url" value="{{ old('url', $project->url) }}" placeholder="https://example.com" required class="mt-1" />
                    <p class="mt-1 text-sm text-slate-500">Use the public website root. Paths, query strings, fragments, and credentials are not supported.</p>
                </div>
                <div>
                    <label for="description" class="text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">{{ old('description', $project->description) }}</textarea>
                </div>
                <x-input-error :messages="$errors->all()" />
                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="submit">Save Changes</x-button>
                    <x-button :href="route('projects.show', $project)" variant="secondary">Cancel</x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <h2 class="font-semibold text-slate-950">Project context</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-500">Canonical domain</dt><dd class="mt-1 break-words font-medium text-slate-950">{{ $project->domain }}</dd></div>
                    <div><dt class="text-slate-500">Current URL</dt><dd class="mt-1 break-words font-medium text-slate-950">{{ $project->url }}</dd></div>
                </dl>
            </x-card>
            <x-card>
                <h2 class="font-semibold text-slate-950">Delete project</h2>
                <p class="mt-2 text-sm text-slate-600">Deleting removes this website, keywords, crawls, issues, and reports from this workspace.</p>
                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-4" onsubmit="return confirm('Delete this project and all related monitoring data?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">Delete Project</button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
