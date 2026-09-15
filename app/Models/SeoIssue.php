<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoIssue extends Model
{
    use HasFactory;

    public const SEVERITIES = ['critical', 'high', 'medium', 'low'];

    protected $fillable = ['project_id', 'type', 'severity', 'page_url', 'message', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function crawl()
    {
        return $this->belongsTo(Crawl::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }
}
