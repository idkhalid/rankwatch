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
        return $this->hasOne(KeywordRanking::class)->latestOfMany('checked_at');
    }

    public function getCurrentPositionAttribute(): ?int
    {
        return $this->rankings->sortByDesc('checked_at')->first()?->position;
    }

    public function getPreviousPositionAttribute(): ?int
    {
        return $this->rankings->sortByDesc('checked_at')->skip(1)->first()?->position;
    }

    public function getBestPositionAttribute(): ?int
    {
        return $this->rankings->whereNotNull('position')->min('position');
    }

    public function getPositionChangeAttribute(): ?int
    {
        if (! $this->current_position || ! $this->previous_position) {
            return null;
        }

        return $this->previous_position - $this->current_position;
    }
}
