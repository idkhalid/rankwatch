<?php

use App\Services\ProjectUrlNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $normalizer = app(ProjectUrlNormalizer::class);
        $seen = [];

        foreach (DB::table('projects')->orderBy('id')->get(['id', 'user_id', 'url']) as $project) {
            $url = $normalizer->normalize($project->url);
            $key = $project->user_id.'|'.$url;

            if (isset($seen[$key])) {
                throw new RuntimeException("Cannot add projects user/url unique index; projects {$seen[$key]} and {$project->id} normalize to {$url}.");
            }

            $seen[$key] = $project->id;

            DB::table('projects')
                ->where('id', $project->id)
                ->update([
                    'url' => $url,
                    'domain' => strtolower(parse_url($url, PHP_URL_HOST) ?: ''),
                ]);
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->unique(['user_id', 'url'], 'projects_user_id_url_unique');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique('projects_user_id_url_unique');
        });
    }
};
