<?php

namespace App\Services;

use App\Models\Keyword;
use Illuminate\Support\Facades\Http;

class RankingChecker
{
    public function check(Keyword $keyword): ?int
    {
        if (config('rankwatch.ranking_api_url')) {
            $response = Http::timeout(15)
                ->withToken(config('rankwatch.ranking_api_key'))
                ->get(config('rankwatch.ranking_api_url'), [
                    'keyword' => $keyword->keyword,
                    'url' => $keyword->target_url ?: $keyword->project->url,
                    'country' => $keyword->country,
                    'device' => $keyword->device,
                ]);

            return $response->ok() ? $response->json('position') : null;
        }

        // ponytail: deterministic demo fallback; replace with a SERP API when real tracking is needed.
        return crc32($keyword->keyword.$keyword->country.$keyword->device.now()->toDateString()) % 60 + 1;
    }
}
