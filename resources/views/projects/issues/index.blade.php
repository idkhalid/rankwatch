<x-app-layout>
    <x-slot:title>Issues - {{ $project->name }}</x-slot:title>
    <x-slot:heading>SEO Issues</x-slot:heading>
    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('projects.issues.index', $project) }}" class="rounded-md px-3 py-2 text-sm {{ $severity ? 'bg-white' : 'bg-slate-950 text-white' }}">All</a>
        @foreach (\App\Models\SeoIssue::SEVERITIES as $level)
            <a href="{{ route('projects.issues.index', [$project, 'severity' => $level]) }}" class="rounded-md px-3 py-2 text-sm {{ $severity === $level ? 'bg-slate-950 text-white' : 'bg-white' }}">{{ ucfirst($level) }}</a>
        @endforeach
    </div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-slate-500"><tr><th class="py-2">Issue</th><th>Severity</th><th>URL</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($issues as $issue)
                    <tr>
                        <td class="py-3">{{ $issue->message }}</td>
                        <td><x-badge :tone="$issue->severity">{{ ucfirst($issue->severity) }}</x-badge></td>
                        <td class="max-w-xs truncate">{{ $issue->page_url }}</td>
                        <td>{{ $issue->resolved_at ? 'Resolved' : 'Open' }}</td>
                        <td class="text-right">
                            @unless ($issue->resolved_at)
                                <form method="POST" action="{{ route('projects.issues.update', [$project, $issue]) }}">@csrf @method('PATCH')<button class="text-slate-600 hover:text-slate-950">Resolve</button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-slate-500">No issues match this filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $issues->links() }}</div>
    </x-card>
</x-app-layout>
