<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SeoScoreCalculator;

class ReportController extends Controller
{
    public function __invoke(Project $project, SeoScoreCalculator $calculator)
    {
        $this->authorize('view', $project);

        $project->load(['latestCompletedCrawl.issues' => fn ($query) => $query->open()->latest()]);
        $keywords = $project->keywords()->withRankingSummary()->latest()->get();

        $currentIssues = $project->latestCompletedCrawl?->issues ?? collect();

        return view('projects.reports.show', [
            'project' => $project,
            'keywords' => $keywords,
            'issues' => $currentIssues,
            'score' => $calculator->calculate($currentIssues),
        ]);
    }
}
