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
    public function index(SeoScoreCalculator $calculator)
    {
        $user = Auth::user();
        $projectLimit = $user->planLimit('projects');
        $projectCount = $user->projects()->count();
        $projects = $user->projects()
            ->withCount('keywords')
            ->with(['latestCompletedCrawl.issues' => fn ($query) => $query->open()])
            ->latest()
            ->paginate(10);

        $projects->getCollection()->each(function (Project $project) use ($calculator): void {
            $currentIssues = $project->latestCompletedCrawl?->issues ?? collect();
            $project->setAttribute('current_issue_count', $currentIssues->count());
            $project->setAttribute('seo_score', $calculator->calculate($currentIssues));
        });

        return view('projects.index', compact('projects', 'projectCount', 'projectLimit'));
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        $projectCount = Auth::user()->projects()->count();
        $projectLimit = Auth::user()->planLimit('projects');

        return view('projects.create', compact('projectCount', 'projectLimit'));
    }

    public function store(StoreProjectRequest $request)
    {
        $project = $request->user()->projects()->create($request->validated());

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project, SeoScoreCalculator $calculator)
    {
        $this->authorize('view', $project);

        $latestCrawl = $project->latestCompletedCrawl()->first();
        $issueCounts = $latestCrawl
            ? $latestCrawl->issues()->open()->selectRaw('severity, count(*) as aggregate')->groupBy('severity')->pluck('aggregate', 'severity')
            : collect();
        $issues = $latestCrawl ? $latestCrawl->issues()->open()->latest()->limit(6)->get() : collect();
        $keywords = $project->keywords()->withRankingSummary()->latest()->get();

        return view('projects.show', [
            'project' => $project,
            'keywords' => $keywords,
            'issues' => $issues,
            'issueCount' => $issueCounts->sum(),
            'latestCrawl' => $latestCrawl,
            'score' => $calculator->calculateFromSeverityCounts($issueCounts),
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
