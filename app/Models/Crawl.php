<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crawl extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'pages_crawled', 'issues_found', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function issues()
    {
        return $this->hasMany(SeoIssue::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')->whereNotNull('finished_at');
    }
}
