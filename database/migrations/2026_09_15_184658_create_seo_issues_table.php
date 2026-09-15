<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crawl_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('severity')->index();
            $table->string('page_url');
            $table->text('message');
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();

            $table->index(['project_id', 'severity', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_issues');
    }
};
