<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Notifications\KeywordDropped;
use App\Services\RankingChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;

class CheckKeywordRankingsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 60;

    public function __construct(public Keyword $keyword)
    {
        $this->tries = config('rankwatch.ranking_job.tries');
        $this->timeout = config('rankwatch.ranking_job.timeout');
    }

    public static function dispatchIfAvailable(Keyword $keyword): bool
    {
        if (! Cache::add(static::queuedKey($keyword), true, config('rankwatch.ranking_job.lock_ttl'))) {
            return false;
        }

        try {
            static::dispatch($keyword);
        } catch (\Throwable $e) {
            Cache::forget(static::queuedKey($keyword));

            throw $e;
        }

        return true;
    }

    public static function queuedKey(Keyword $keyword): string
    {
        return "rankwatch:ranking:queued:{$keyword->getKey()}";
    }

    public static function overlapKey(Keyword $keyword): string
    {
        return "rankwatch:ranking:{$keyword->getKey()}";
    }

    public function backoff(): int
    {
        return config('rankwatch.ranking_job.backoff');
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(static::overlapKey($this->keyword)))
                ->expireAfter(config('rankwatch.ranking_job.lock_ttl'))
                ->dontRelease()
                ->withPrefix('')
                ->shared(),
        ];
    }

    public function handle(RankingChecker $checker): void
    {
        try {
            $previous = $this->keyword->rankings()
                ->orderByDesc('checked_at')
                ->orderByDesc('id')
                ->first()?->position;
            $result = $checker->check($this->keyword);

            $this->keyword->rankings()->create([
                'position' => $result['position'],
                'status' => $result['status'],
                'checked_at' => now(),
            ]);

            $drop = $previous && $result['position'] ? $result['position'] - $previous : 0;
            if ($drop >= 5 && $this->keyword->project->user->email_notifications) {
                $this->keyword->project->user->notify(new KeywordDropped($this->keyword, $drop));
            }
        } finally {
            Cache::forget(static::queuedKey($this->keyword));
        }
    }
}
