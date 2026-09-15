<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Notifications\KeywordDropped;
use App\Services\RankingChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckKeywordRankingsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Keyword $keyword)
    {
    }

    public function handle(RankingChecker $checker): void
    {
        $previous = $this->keyword->rankings()->latest('checked_at')->first()?->position;
        $position = $checker->check($this->keyword);

        $this->keyword->rankings()->create([
            'position' => $position,
            'checked_at' => now(),
        ]);

        $drop = $previous && $position ? $position - $previous : 0;
        if ($drop >= 5 && $this->keyword->project->user->email_notifications) {
            $this->keyword->project->user->notify(new KeywordDropped($this->keyword, $drop));
        }
    }
}
