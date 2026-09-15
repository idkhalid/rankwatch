<x-app-layout>
    <x-slot:title>SEO Report - {{ $project->name }}</x-slot:title>
    <x-slot:heading>SEO Report</x-slot:heading>
    <style>@media print { aside, header form, .no-print { display:none!important } main { padding:0!important } }</style>
    <div class="no-print mb-5"><button onclick="window.print()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Print report</button></div>
    <x-card>
        <h2 class="text-2xl font-bold">{{ $project->name }}</h2>
        <p class="mt-1 text-slate-500">{{ $project->url }}</p>
        <div class="mt-6 grid gap-4 sm:grid-cols-4">
            <div><p class="text-sm text-slate-500">SEO score</p><p class="text-3xl font-bold">{{ $score }}</p></div>
            <div><p class="text-sm text-slate-500">Keywords</p><p class="text-3xl font-bold">{{ $project->keywords->count() }}</p></div>
            <div><p class="text-sm text-slate-500">Open issues</p><p class="text-3xl font-bold">{{ $issues->count() }}</p></div>
            <div><p class="text-sm text-slate-500">Pages crawled</p><p class="text-3xl font-bold">{{ $project->latestCompletedCrawl?->pages_crawled ?? 0 }}</p></div>
        </div>
    </x-card>
    <x-card class="mt-6">
        <h2 class="font-semibold">Keyword summary</h2>
        <table class="mt-3 w-full text-left text-sm">
            <thead class="text-slate-500"><tr><th class="py-2">Keyword</th><th>Current</th><th>Change</th></tr></thead>
            <tbody class="divide-y divide-slate-200">
            @foreach ($project->keywords as $keyword)
                <tr><td class="py-2">{{ $keyword->keyword }}</td><td>{{ $keyword->current_position_label }}</td><td>{{ $keyword->position_change ?? '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </x-card>
    <x-card class="mt-6">
        <h2 class="font-semibold">SEO issues</h2>
        <div class="mt-3 divide-y divide-slate-200">
            @foreach ($issues as $issue)
                <div class="py-3"><x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge><p class="mt-2 text-sm">{{ $issue->message }}</p><p class="text-xs text-slate-500">{{ $issue->page_url }}</p></div>
            @endforeach
        </div>
    </x-card>
</x-app-layout>
