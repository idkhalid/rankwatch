<?php

namespace App\Models;

use App\Services\ProjectUrlNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'domain', 'description'];

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            $project->url = app(ProjectUrlNormalizer::class)->normalize($project->url);
            $project->domain = strtolower(parse_url($project->url, PHP_URL_HOST) ?: $project->domain);
            $project->slug = static::uniqueSlug($project->name);
        });

        static::updating(function (Project $project) {
            $project->url = app(ProjectUrlNormalizer::class)->normalize($project->url);
            $project->domain = strtolower(parse_url($project->url, PHP_URL_HOST) ?: $project->domain);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }

    public function crawls()
    {
        return $this->hasMany(Crawl::class);
    }

    public function seoIssues()
    {
        return $this->hasMany(SeoIssue::class);
    }

    public function latestCrawl()
    {
        return $this->hasOne(Crawl::class)->latestOfMany();
    }

    public function latestCompletedCrawl()
    {
        return $this->hasOne(Crawl::class)
            ->completed()
            ->ofMany(['finished_at' => 'max', 'id' => 'max']);
    }

    private static function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
