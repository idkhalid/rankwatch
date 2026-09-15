<x-app-layout>
    <x-slot:heading>Edit Project</x-slot:heading>
    <x-card>
        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div><label class="text-sm font-medium">Name</label><x-input name="name" value="{{ old('name', $project->name) }}" required /></div>
            <div><label class="text-sm font-medium">Website URL</label><x-input name="url" type="url" value="{{ old('url', $project->url) }}" required /></div>
            <div><label class="text-sm font-medium">Description</label><textarea name="description" class="block w-full rounded-md border-slate-300">{{ old('description', $project->description) }}</textarea></div>
            <x-input-error :messages="$errors->all()" />
            <div class="flex gap-3"><x-button>Save changes</x-button></div>
        </form>
        <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-6">
            @csrf
            @method('DELETE')
            <button class="text-sm text-red-600">Delete project</button>
        </form>
    </x-card>
</x-app-layout>
