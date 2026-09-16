<?php

namespace App\Http\Controllers;

use App\Models\Crawl;
use App\Models\KeywordRanking;
use App\Models\SeoIssue;
use App\Services\SeoScoreCalculator;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SeoScoreCalculator $calculator)
    {
        $user = $request->user();

        $currentIssueCounts = SeoIssue::query()
            ->whereNull('resolved_at')
            ->whereIn('crawl_id', $this->latestCompletedCrawlIds($user->id))
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        $projects = $request->user()->projects()
            ->withCount('keywords')
            ->latest()
            ->limit(10)
            ->get();

        $averagePosition = (clone $this->latestUserRankings($user->id))
            ->where('current.status', KeywordRanking::STATUS_FOUND)
            ->whereNotNull('current.position')
            ->avg('current.position');

        $lastCrawl = Crawl::query()
            ->join('projects', 'projects.id', '=', 'crawls.project_id')
            ->where('projects.user_id', $user->id)
            ->completed()
            ->orderByDesc('crawls.finished_at')
            ->orderByDesc('crawls.id')
            ->select('crawls.*')
            ->first();

        return view('dashboard.index', [
            'projects' => $projects,
            'projectCount' => $user->projects()->count(),
            'score' => $calculator->calculateFromSeverityCounts($currentIssueCounts),
            'keywordCount' => $user->projects()->join('keywords', 'keywords.project_id', '=', 'projects.id')->count(),
            'averagePosition' => round($averagePosition ?? 0, 1),
            'positionChanges' => 0,
            'issueCount' => $currentIssueCounts->sum(),
            'lastCrawl' => $lastCrawl,
        ]);
    }

    private function latestCompletedCrawlIds(int $userId)
    {
        return Crawl::query()
            ->from('crawls as current_crawls')
            ->join('projects', 'projects.id', '=', 'current_crawls.project_id')
            ->where('projects.user_id', $userId)
            ->where('current_crawls.status', 'completed')
            ->whereNotNull('current_crawls.finished_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('crawls as newer_crawls')
                    ->whereColumn('newer_crawls.project_id', 'current_crawls.project_id')
                    ->where('newer_crawls.status', 'completed')
                    ->whereNotNull('newer_crawls.finished_at')
                    ->where(function ($query): void {
                        $query->whereColumn('newer_crawls.finished_at', '>', 'current_crawls.finished_at')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('newer_crawls.finished_at', 'current_crawls.finished_at')
                                    ->whereColumn('newer_crawls.id', '>', 'current_crawls.id');
                            });
                    });
            })
            ->select('current_crawls.id');
    }

    private function latestUserRankings(int $userId)
    {
        return KeywordRanking::query()
            ->from('keyword_rankings as current')
            ->join('keywords', 'keywords.id', '=', 'current.keyword_id')
            ->join('projects', 'projects.id', '=', 'keywords.project_id')
            ->where('projects.user_id', $userId)
            ->whereIn('current.status', [KeywordRanking::STATUS_FOUND, KeywordRanking::STATUS_NOT_FOUND])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('keyword_rankings as newer')
                    ->whereColumn('newer.keyword_id', 'current.keyword_id')
                    ->whereIn('newer.status', [KeywordRanking::STATUS_FOUND, KeywordRanking::STATUS_NOT_FOUND])
                    ->where(function ($query): void {
                        $query->whereColumn('newer.checked_at', '>', 'current.checked_at')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('newer.checked_at', 'current.checked_at')
                                    ->whereColumn('newer.id', '>', 'current.id');
                            });
                    });
            });
    }
}
