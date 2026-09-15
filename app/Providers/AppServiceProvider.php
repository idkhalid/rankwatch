<?php

namespace App\Providers;

use App\Models\Project;
use App\Policies\ProjectPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);

        RateLimiter::for('manual-crawl', function (Request $request) {
            $project = $request->route('project');
            $projectKey = $project instanceof Project ? $project->getKey() : (string) $project;

            return [
                Limit::perMinute(5)->by('user:'.($request->user()?->id ?? $request->ip())),
                Limit::perMinute(2)->by('project:'.$projectKey),
            ];
        });
    }
}
