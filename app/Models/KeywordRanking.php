<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordRanking extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['position', 'checked_at'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function keyword()
    {
        return $this->belongsTo(Keyword::class);
    }
}
