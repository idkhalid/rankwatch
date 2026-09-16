<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Keyword extends Model
{
    use HasFactory;

    protected $fillable = ['keyword', 'target_url', 'country', 'device'];

    private ?Collection $rankingSnapshot = null;

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

    public function recentRankings()
    {
        return $this->hasMany(KeywordRanking::class)
            ->successful()
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->limit(2);
    }

    public function scopeWithRankingSummary($query)
    {
        return $query
            ->with(['recentRankings'])
            ->withMin(['rankings as best_position_value' => fn ($query) => $query
                ->where('status', KeywordRanking::STATUS_FOUND)
                ->whereNotNull('position')
            ], 'position');
    }

    public function getCurrentPositionAttribute(): ?int
    {
        $ranking = $this->rankingSnapshot()->first();

        return $ranking?->status === KeywordRanking::STATUS_FOUND ? $ranking->position : null;
    }

    public function getPreviousPositionAttribute(): ?int
    {
        $ranking = $this->rankingSnapshot()->skip(1)->first();

        return $ranking?->status === KeywordRanking::STATUS_FOUND ? $ranking->position : null;
    }

    public function getBestPositionAttribute(): ?int
    {
        if (array_key_exists('best_position_value', $this->attributes)) {
            return $this->attributes['best_position_value'] === null ? null : (int) $this->attributes['best_position_value'];
        }

        return $this->rankings()
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
        return $this->positionLabel($this->rankingSnapshot()->first());
    }

    public function getPreviousPositionLabelAttribute(): string
    {
        return $this->positionLabel($this->rankingSnapshot()->skip(1)->first());
    }

    private function rankingSnapshot(): Collection
    {
        if ($this->relationLoaded('recentRankings')) {
            return $this->recentRankings;
        }

        return $this->rankingSnapshot ??= $this->rankings()
            ->successful()
            ->orderByDesc('checked_at')
            ->orderByDesc('id')
            ->limit(2)
            ->get();
    }

    private function positionLabel(?KeywordRanking $ranking): string
    {
        if (! $ranking) {
            return '-';
        }

        return $ranking->status === KeywordRanking::STATUS_NOT_FOUND ? 'Not found' : (string) $ranking->position;
    }
}
