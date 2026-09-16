<x-app-layout>
    <x-slot:heading>Add Keyword</x-slot:heading>
    @if ($project->keywords()->count() >= auth()->user()->planLimit('keywords_per_project'))
        <div class="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">This website is at {{ $project->keywords()->count() }} / {{ auth()->user()->planLimit('keywords_per_project') }} keywords. Upgrade to Pro for more capacity.</div>
    @endif
    <x-card>
        <form method="POST" action="{{ route('keywords.store', $project) }}" class="space-y-4">
            @csrf
            <div><label class="text-sm font-medium">Keyword</label><x-input name="keyword" value="{{ old('keyword') }}" required /></div>
            <div><label class="text-sm font-medium">Target URL</label><x-input name="target_url" type="url" value="{{ old('target_url') }}" /></div>
            <div><label class="text-sm font-medium">Country</label><x-input name="country" value="{{ old('country', 'Indonesia') }}" required /></div>
            <div><label class="text-sm font-medium">Device</label><select name="device" class="block w-full rounded-md border-slate-300"><option value="desktop">Desktop</option><option value="mobile">Mobile</option></select></div>
            <x-input-error :messages="$errors->all()" />
            <x-button>Add keyword</x-button>
        </form>
    </x-card>
</x-app-layout>
