<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\KeywordRanking;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RankingChecker
{
    public function check(Keyword $keyword): array
    {
        if (config('rankwatch.ranking_api_url')) {
            try {
                $response = Http::timeout(config('rankwatch.ranking_provider_timeout'))
                    ->withToken(config('rankwatch.ranking_api_key'))
                    ->get(config('rankwatch.ranking_api_url'), [
                        'keyword' => $keyword->keyword,
                        'url' => $keyword->target_url ?: $keyword->project->url,
                        'country' => $keyword->country,
                        'device' => $keyword->device,
                    ]);
            } catch (ConnectionException $e) {
                throw new RuntimeException('Ranking provider connection failed.', 0, $e);
            }

            if ($response->status() === 429) {
                throw new RuntimeException('Ranking provider rate limit reached.');
            }

            if ($response->serverError()) {
                throw new RuntimeException('Ranking provider server error.');
            }

            if (! $response->ok()) {
                throw new RuntimeException('Ranking provider request failed.');
            }

            try {
                $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new RuntimeException('Ranking provider returned invalid JSON.', 0, $e);
            }

            if (! is_array($payload)) {
                throw new RuntimeException('Ranking provider returned an invalid payload.');
            }

            return $this->parsePayload($payload);
        }

        // ponytail: deterministic demo fallback; replace with a SERP API when real tracking is needed.
        return [
            'status' => KeywordRanking::STATUS_FOUND,
            'position' => crc32($keyword->keyword.$keyword->country.$keyword->device.now()->toDateString()) % 60 + 1,
        ];
    }

    private function parsePayload(array $payload): array
    {
        if (($payload['found'] ?? null) === false || (array_key_exists('position', $payload) && $payload['position'] === null)) {
            return ['status' => KeywordRanking::STATUS_NOT_FOUND, 'position' => null];
        }

        if (! array_key_exists('position', $payload)) {
            throw new RuntimeException('Ranking provider response is missing position.');
        }

        $position = $payload['position'];

        if (is_string($position) && ctype_digit($position)) {
            $position = (int) $position;
        }

        if (! is_int($position) || $position < 1 || $position > config('rankwatch.ranking_max_position')) {
            throw new RuntimeException('Ranking provider returned an invalid position.');
        }

        return ['status' => KeywordRanking::STATUS_FOUND, 'position' => $position];
    }
}
