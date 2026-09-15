<x-app-layout>
    <x-slot:heading>Edit Keyword</x-slot:heading>
    <x-card>
        <form method="POST" action="{{ route('keywords.update', [$project, $keyword]) }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div><label class="text-sm font-medium">Keyword</label><x-input name="keyword" value="{{ old('keyword', $keyword->keyword) }}" required /></div>
            <div><label class="text-sm font-medium">Target URL</label><x-input name="target_url" type="url" value="{{ old('target_url', $keyword->target_url) }}" /></div>
            <div><label class="text-sm font-medium">Country</label><x-input name="country" value="{{ old('country', $keyword->country) }}" required /></div>
            <div><label class="text-sm font-medium">Device</label><select name="device" class="block w-full rounded-md border-slate-300"><option @selected($keyword->device === 'desktop') value="desktop">Desktop</option><option @selected($keyword->device === 'mobile') value="mobile">Mobile</option></select></div>
            <x-input-error :messages="$errors->all()" />
            <x-button>Save keyword</x-button>
        </form>
        <form method="POST" action="{{ route('keywords.destroy', [$project, $keyword]) }}" class="mt-6">
            @csrf
            @method('DELETE')
            <button class="text-sm text-red-600">Delete keyword</button>
        </form>
    </x-card>
</x-app-layout>
