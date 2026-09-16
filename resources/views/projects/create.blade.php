<x-app-layout>
    <x-slot:heading>New Project</x-slot:heading>
    @if (auth()->user()->projects()->count() >= auth()->user()->planLimit('projects'))
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Your {{ ucfirst(auth()->user()->plan) }} plan supports {{ auth()->user()->planLimit('projects') }} {{ auth()->user()->planLimit('projects') === 1 ? 'website' : 'websites' }}. Upgrade to Pro to monitor additional websites.</div>
    @endif
    <x-card>
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
            @csrf
            <div><label class="text-sm font-medium">Name</label><x-input name="name" value="{{ old('name') }}" required /></div>
            <div><label class="text-sm font-medium">Website URL</label><x-input name="url" type="url" value="{{ old('url') }}" placeholder="https://example.com" required /><p class="mt-1 text-sm text-slate-500">Use the website root. Paths, query strings, fragments, and credentials are not supported.</p></div>
            <div><label class="text-sm font-medium">Description</label><textarea name="description" class="block w-full rounded-md border-slate-300">{{ old('description') }}</textarea></div>
            <x-input-error :messages="$errors->all()" />
            <x-button>Create project</x-button>
        </form>
    </x-card>
</x-app-layout>
