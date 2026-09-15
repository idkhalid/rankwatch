<?php

namespace App\Http\Controllers;

use App\Services\SeoScoreCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SeoScoreCalculator $calculator)
    {
        $projects = $request->user()->projects()
            ->with(['keywords.rankings', 'latestCompletedCrawl.issues' => fn ($query) => $query->open()])
            ->latest()
            ->get();

        $keywords = $projects->flatMap->keywords;
        $issues = $projects->flatMap(fn ($project) => $project->latestCompletedCrawl?->issues ?? collect());

        return view('dashboard.index', [
            'projects' => $projects,
            'score' => $calculator->calculate($issues),
            'keywordCount' => $keywords->count(),
            'averagePosition' => round($keywords->pluck('current_position')->filter()->avg() ?? 0, 1),
            'positionChanges' => $keywords->pluck('position_change')->filter()->sum(),
            'issueCount' => $issues->count(),
            'lastCrawl' => $projects->pluck('latestCompletedCrawl')->filter()->sortByDesc('finished_at')->first(),
        ]);
    }
}
