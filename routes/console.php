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
    Project::query()->chunkById(100, fn ($projects) => $projects->each(
        fn (Project $project) => CrawlProjectJob::dispatchIfAvailable($project)
    ));

    Keyword::query()->chunkById(100, fn ($keywords) => $keywords->each(
        fn (Keyword $keyword) => CheckKeywordRankingsJob::dispatchIfAvailable($keyword)
    ));
})->name('rankwatch-daily-monitoring')->dailyAt('02:00')->withoutOverlapping();
