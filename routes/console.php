<?php

use App\Jobs\CheckKeywordRankingsJob;
use App\Jobs\CrawlProjectJob;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Project::query()
        ->with(['user', 'latestCompletedCrawl'])
        ->chunkById(100, fn ($projects) => $projects->each(function (Project $project): void {
            $last = $project->latestCompletedCrawl?->finished_at;
            $days = $project->user->monitoringIntervalDays('crawl_frequency');

            if (! $last || $last->lte(now()->subDays($days))) {
                CrawlProjectJob::dispatchIfAvailable($project);
            }
        }));

    Keyword::query()
        ->with(['latestRanking', 'project.user'])
        ->chunkById(100, fn ($keywords) => $keywords->each(function (Keyword $keyword): void {
            $last = $keyword->latestRanking?->checked_at;
            $days = $keyword->project->user->monitoringIntervalDays('ranking_frequency');

            if (! $last || $last->lte(now()->subDays($days))) {
                CheckKeywordRankingsJob::dispatchIfAvailable($keyword);
            }
        }));
})->name('rankwatch-daily-monitoring')->dailyAt('02:00')->withoutOverlapping();
