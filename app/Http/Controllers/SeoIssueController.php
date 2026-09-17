<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SeoIssue;
use Illuminate\Http\Request;

class SeoIssueController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $severity = $request->string('severity')->toString();
        $crawl = $project->latestCompletedCrawl()->first();
        $issueQuery = $crawl ? $crawl->issues()->open() : SeoIssue::query()->whereRaw('1 = 0');
        $severityCounts = $crawl
            ? (clone $issueQuery)->selectRaw('severity, count(*) as aggregate')->groupBy('severity')->pluck('aggregate', 'severity')
            : collect();

        $issues = $issueQuery
            ->when(in_array($severity, SeoIssue::SEVERITIES, true), fn ($query) => $query->where('severity', $severity))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('projects.issues.index', compact('project', 'issues', 'severity', 'crawl', 'severityCounts'));
    }

    public function update(Project $project, SeoIssue $issue)
    {
        $this->authorize('update', $project);
        $crawl = $project->latestCompletedCrawl()->first();

        abort_unless($issue->project_id === $project->id && $crawl?->is($issue->crawl), 404);

        $issue->update(['resolved_at' => now()]);

        return back()->with('status', 'Issue marked as resolved.');
    }
}
