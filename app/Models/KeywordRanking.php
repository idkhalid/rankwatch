<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordRanking extends Model
{
    use HasFactory;

    public const STATUS_FOUND = 'found';

    public const STATUS_NOT_FOUND = 'not_found';

    public $timestamps = false;

    protected $fillable = ['position', 'status', 'checked_at'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_FOUND, self::STATUS_NOT_FOUND]);
    }

    public function getPositionLabelAttribute(): string
    {
        return $this->status === self::STATUS_NOT_FOUND ? 'Not found' : (string) $this->position;
    }
}
