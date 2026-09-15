<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = ['keyword', 'target_url', 'country', 'device'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function rankings()
    {
        return $this->hasMany(KeywordRanking::class);
    }

    public function latestRanking()
    {
        return $this->hasOne(KeywordRanking::class)->ofMany(['checked_at' => 'max', 'id' => 'max']);
    }

    public function getCurrentPositionAttribute(): ?int
    {
        $ranking = $this->orderedRankings()->first();

        return $ranking?->status === KeywordRanking::STATUS_FOUND ? $ranking->position : null;
    }

    public function getPreviousPositionAttribute(): ?int
    {
        $ranking = $this->orderedRankings()->skip(1)->first();

        return $ranking?->status === KeywordRanking::STATUS_FOUND ? $ranking->position : null;
    }

    public function getBestPositionAttribute(): ?int
    {
        return $this->rankings
            ->where('status', KeywordRanking::STATUS_FOUND)
            ->whereNotNull('position')
            ->min('position');
    }

    public function getPositionChangeAttribute(): ?int
    {
        if (! $this->current_position || ! $this->previous_position) {
            return null;
        }

        return $this->previous_position - $this->current_position;
    }

    public function getCurrentPositionLabelAttribute(): string
    {
        return $this->positionLabel($this->orderedRankings()->first());
    }

    public function getPreviousPositionLabelAttribute(): string
    {
        return $this->positionLabel($this->orderedRankings()->skip(1)->first());
    }

    private function orderedRankings()
    {
        return $this->rankings->sort(function (KeywordRanking $a, KeywordRanking $b) {
            $time = ($b->checked_at?->getTimestamp() ?? 0) <=> ($a->checked_at?->getTimestamp() ?? 0);

            return $time !== 0 ? $time : $b->id <=> $a->id;
        })->values();
    }

    private function positionLabel(?KeywordRanking $ranking): string
    {
        if (! $ranking) {
            return '-';
        }

        return $ranking->status === KeywordRanking::STATUS_NOT_FOUND ? 'Not found' : (string) $ranking->position;
    }
}
