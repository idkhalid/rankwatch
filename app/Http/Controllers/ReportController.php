<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SeoScoreCalculator;

class ReportController extends Controller
{
    public function __invoke(Project $project, SeoScoreCalculator $calculator)
    {
        $this->authorize('view', $project);

        $project->load(['keywords.rankings', 'latestCompletedCrawl.issues' => fn ($query) => $query->open()->latest()]);

        $currentIssues = $project->latestCompletedCrawl?->issues ?? collect();

        return view('projects.reports.show', [
            'project' => $project,
            'issues' => $currentIssues,
            'score' => $calculator->calculate($currentIssues),
        ]);
    }
}
