<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Jobs\CrawlProjectJob;
use App\Models\Project;
use App\Services\SeoScoreCalculator;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Auth::user()->projects()->withCount(['keywords', 'seoIssues'])->latest()->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(StoreProjectRequest $request)
    {
        $project = $request->user()->projects()->create($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project, SeoScoreCalculator $calculator)
    {
        $this->authorize('view', $project);

        $project->load(['latestCompletedCrawl.issues' => fn ($query) => $query->open()->latest()->limit(6), 'keywords.rankings']);

        $currentIssues = $project->latestCompletedCrawl?->issues ?? collect();

        return view('projects.show', [
            'project' => $project,
            'issues' => $currentIssues,
            'score' => $calculator->calculate($currentIssues),
        ]);
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        return view('projects.edit', compact('project'));
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    public function crawl(Project $project)
    {
        $this->authorize('update', $project);

        if (! CrawlProjectJob::dispatchIfAvailable($project)) {
            return back()->with('status', 'A crawl is already queued or running for this project.');
        }

        return back()->with('status', 'Crawl queued.');
    }
}
