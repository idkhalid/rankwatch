<x-app-layout>
    <x-slot:heading>New Project</x-slot:heading>
    <x-card>
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
            @csrf
            <div><label class="text-sm font-medium">Name</label><x-input name="name" value="{{ old('name') }}" required /></div>
            <div><label class="text-sm font-medium">Website URL</label><x-input name="url" type="url" value="{{ old('url') }}" required /></div>
            <div><label class="text-sm font-medium">Description</label><textarea name="description" class="block w-full rounded-md border-slate-300">{{ old('description') }}</textarea></div>
            <x-input-error :messages="$errors->all()" />
            <x-button>Create project</x-button>
        </form>
    </x-card>
</x-app-layout>
