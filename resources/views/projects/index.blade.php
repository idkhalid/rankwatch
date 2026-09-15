<x-app-layout>
    <x-slot:title>Projects - RankWatch</x-slot:title>
    <x-slot:heading>Projects</x-slot:heading>
    <div class="mb-5 flex justify-end"><x-button :href="route('projects.create')">New project</x-button></div>
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-slate-500"><tr><th class="py-2">Project</th><th>Keywords</th><th>Issues</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-200">
                @forelse ($projects as $project)
                    <tr>
                        <td class="py-3"><a class="font-medium hover:underline" href="{{ route('projects.show', $project) }}">{{ $project->name }}</a><p class="text-slate-500">{{ $project->domain }}</p></td>
                        <td>{{ $project->keywords_count }}</td>
                        <td>{{ $project->seo_issues_count }}</td>
                        <td class="text-right"><a class="text-slate-600 hover:text-slate-950" href="{{ route('projects.edit', $project) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-slate-500">No projects yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $projects->links() }}</div>
    </x-card>
</x-app-layout>
