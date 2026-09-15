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
    Project::query()->each(fn (Project $project) => CrawlProjectJob::dispatchIfAvailable($project));
    Keyword::query()->each(fn (Keyword $keyword) => CheckKeywordRankingsJob::dispatch($keyword));
})->dailyAt('02:00');
