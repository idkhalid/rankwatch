<?php

namespace App\Services;

use App\Models\Crawl;
use Illuminate\Support\Collection;

class SeoScoreCalculator
{
    public function calculateForCrawl(?Crawl $crawl): int
    {
        if (! $crawl) {
            return 100;
        }

        return $this->calculate($crawl->issues()->open()->get());
    }

    public function calculate(Collection $issues): int
    {
        $score = 100;
        $deductions = config('rankwatch.scoring');

        foreach ($issues as $issue) {
            $score -= $deductions[$issue->severity] ?? 0;
        }

        return max(0, $score);
    }

    public function calculateFromSeverityCounts(Collection $severityCounts): int
    {
        $score = 100;
        $deductions = config('rankwatch.scoring');

        foreach ($severityCounts as $severity => $count) {
            $score -= ($deductions[$severity] ?? 0) * $count;
        }

        return max(0, $score);
    }
}
