<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SeoIssueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketingController::class, 'home'])->name('marketing.home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('marketing.pricing');
Route::get('/sitemap.xml', [MarketingController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [MarketingController::class, 'robots'])->name('robots');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/crawl', [ProjectController::class, 'crawl'])
        ->middleware('throttle:manual-crawl')
        ->name('projects.crawl');

    Route::resource('/projects/{project}/keywords', KeywordController::class);
    Route::get('/projects/{project}/issues', [SeoIssueController::class, 'index'])->name('projects.issues.index');
    Route::patch('/projects/{project}/issues/{issue}', [SeoIssueController::class, 'update'])->name('projects.issues.update');
    Route::get('/projects/{project}/report', ReportController::class)->name('projects.report');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
