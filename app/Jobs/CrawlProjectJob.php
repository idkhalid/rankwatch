<?php

namespace App\Jobs;

use App\Models\Project;
use App\Notifications\CriticalIssuesFound;
use App\Notifications\CrawlCompleted;
use App\Services\SeoCrawler;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;

class CrawlProjectJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(public Project $project)
    {
        $this->tries = config('rankwatch.crawl.job_tries');
        $this->timeout = config('rankwatch.crawl.job_timeout');
    }

    public static function dispatchIfAvailable(Project $project): bool
    {
        if (! Cache::add(static::queuedKey($project), true, config('rankwatch.crawl.lock_ttl'))) {
            return false;
        }

        try {
            static::dispatch($project);
        } catch (\Throwable $e) {
            Cache::forget(static::queuedKey($project));

            throw $e;
        }

        return true;
    }

    public static function queuedKey(Project $project): string
    {
        return "rankwatch:crawl:queued:{$project->getKey()}";
    }

    public static function overlapKey(Project $project): string
    {
        return "rankwatch:crawl:{$project->getKey()}";
    }

    public function backoff(): int
    {
        return config('rankwatch.crawl.job_backoff');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(static::overlapKey($this->project)))
                ->expireAfter(config('rankwatch.crawl.lock_ttl'))
                ->dontRelease()
                ->withPrefix('')
                ->shared(),
        ];
    }

    public function handle(SeoCrawler $crawler): void
    {
        try {
            $this->project->load('user');
            $crawl = $crawler->crawl($this->project);
            $user = $this->project->user;

            if (! $user->email_notifications) {
                return;
            }

            if ($user->canUseFeature('crawl_completed_notifications')) {
                $user->notify(new CrawlCompleted($crawl));
            }

            $criticalCount = $crawl->issues()->where('severity', 'critical')->count();
            if ($criticalCount > 0 && $user->canUseFeature('critical_issue_notifications')) {
                $user->notify(new CriticalIssuesFound($this->project, $criticalCount));
            }
        } finally {
            Cache::forget(static::queuedKey($this->project));
        }
    }
}
