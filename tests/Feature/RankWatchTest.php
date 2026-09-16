<?php

use App\Jobs\CheckKeywordRankingsJob;
use App\Jobs\CrawlProjectJob;
use App\Models\Crawl;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Project;
use App\Models\User;
use App\Services\CrawlUrlValidator;
use App\Services\RankingChecker;
use App\Services\SeoCrawler;
use App\Services\SeoScoreCalculator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function useCrawlerDns(array $records): void
{
    app()->bind(CrawlUrlValidator::class, fn () => new CrawlUrlValidator(
        fn (string $host) => $records[strtolower($host)] ?? []
    ));
}

function completedCrawl(Project $project, array $issues = [], $finishedAt = null): Crawl
{
    $finishedAt ??= now();

    $crawl = $project->crawls()->create([
        'status' => 'completed',
        'pages_crawled' => 1,
        'started_at' => $finishedAt->copy()->subMinute(),
        'finished_at' => $finishedAt,
    ]);

    foreach ($issues as $issue) {
        $crawl->issues()->create([
            'project_id' => $project->id,
            'type' => $issue['type'],
            'severity' => $issue['severity'],
            'page_url' => $issue['page_url'] ?? $project->url,
            'message' => $issue['message'],
            'resolved_at' => $issue['resolved_at'] ?? null,
        ]);
    }

    $crawl->update(['issues_found' => $crawl->issues()->count()]);

    return $crawl->refresh();
}

function rankwatchScheduleEvent()
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'rankwatch-daily-monitoring');
}

function demoKeyword(): Keyword
{
    return User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create([
            'keyword' => 'coffee beans',
            'target_url' => 'https://acme.test/beans',
            'country' => 'Indonesia',
            'device' => 'desktop',
        ]);
}

it('lets a verified user create a project', function () {
    useCrawlerDns(['example.com' => ['93.184.216.34']]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'Acme Coffee',
            'url' => 'https://example.com',
            'description' => 'Coffee shop',
        ])
        ->assertRedirect();

    expect($user->projects()->first())
        ->name->toBe('Acme Coffee')
        ->domain->toBe('example.com');
});

it('blocks users from viewing other projects', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = $owner->projects()->create(['name' => 'Owner Site', 'url' => 'https://owner.test']);

    $this->actingAs($intruder)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

it('blocks users from editing updating or deleting other users projects', function () {
    useCrawlerDns(['changed.test' => ['93.184.216.34']]);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = $owner->projects()->create(['name' => 'Owner Site', 'url' => 'https://owner.test']);

    $this->actingAs($intruder)
        ->get(route('projects.edit', $project))
        ->assertForbidden();

    $this->actingAs($intruder)
        ->patch(route('projects.update', $project), [
            'name' => 'Changed',
            'url' => 'https://changed.test/',
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();

    expect($project->refresh()->name)->toBe('Owner Site')
        ->and($project->url)->toBe('https://owner.test/')
        ->and(Project::query()->whereKey($project->id)->exists())->toBeTrue();
});

it('rejects mismatched project and keyword route pairs', function (string $route, string $method) {
    $firstUser = User::factory()->create();
    $firstProject = $firstUser->projects()->create(['name' => 'First', 'url' => 'https://first.test']);
    $firstKeyword = $firstProject->keywords()->create(['keyword' => 'first keyword', 'country' => 'Indonesia', 'device' => 'desktop']);

    $secondUser = User::factory()->create();
    $secondProject = $secondUser->projects()->create(['name' => 'Second', 'url' => 'https://second.test']);
    $secondKeyword = $secondProject->keywords()->create(['keyword' => 'second keyword', 'country' => 'Indonesia', 'device' => 'desktop']);

    $payload = ['keyword' => 'changed keyword', 'country' => 'Indonesia', 'device' => 'desktop'];

    foreach ([[$firstUser, $firstProject, $secondKeyword], [$secondUser, $secondProject, $firstKeyword]] as [$user, $project, $keyword]) {
        $response = in_array($method, ['get', 'delete'], true)
            ? $this->actingAs($user)->{$method}(route($route, [$project, $keyword]))
            : $this->actingAs($user)->{$method}(route($route, [$project, $keyword]), $payload);

        $response->assertNotFound();
    }

    expect($firstKeyword->refresh()->keyword)->toBe('first keyword')
        ->and($secondKeyword->refresh()->keyword)->toBe('second keyword')
        ->and(Keyword::query()->whereKey($firstKeyword->id)->exists())->toBeTrue()
        ->and(Keyword::query()->whereKey($secondKeyword->id)->exists())->toBeTrue();
})->with([
    'show' => ['keywords.show', 'get'],
    'edit' => ['keywords.edit', 'get'],
    'update' => ['keywords.update', 'patch'],
    'delete' => ['keywords.destroy', 'delete'],
]);

it('creates keywords and stores ranking history', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $this->actingAs($user)
        ->post(route('keywords.store', $project), [
            'keyword' => 'best coffee beans jakarta',
            'target_url' => 'https://acme.test/beans',
            'country' => 'Indonesia',
            'device' => 'desktop',
        ])
        ->assertRedirect();

    $keyword = $project->keywords()->first();
    config(['rankwatch.ranking_api_url' => null]);

    (new CheckKeywordRankingsJob($keyword))->handle(app(\App\Services\RankingChecker::class));

    expect($keyword->rankings()->count())->toBe(1);
});

it('stores a valid provider position as a found ranking', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['position' => 12])]);

    $keyword = demoKeyword();

    (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class));
    $ranking = $keyword->rankings()->first();

    expect($ranking->status)->toBe(KeywordRanking::STATUS_FOUND)
        ->and($ranking->position)->toBe(12);
});

it('stores a successful not found result distinctly from provider failure', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['found' => false, 'position' => null])]);

    $keyword = demoKeyword();

    (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class));
    $ranking = $keyword->rankings()->first();

    expect($ranking->status)->toBe(KeywordRanking::STATUS_NOT_FOUND)
        ->and($ranking->position)->toBeNull()
        ->and($keyword->refresh()->current_position_label)->toBe('Not found');
});

it('does not create ranking history when the provider times out', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(fn () => throw new ConnectionException('timeout'));

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
});

it('does not create fake ranking history for provider rate limits', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['message' => 'too many'], 429)]);

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
});

it('does not create ranking history for provider server errors', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['message' => 'bad'], 500)]);

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
});

it('does not create ranking history for invalid provider json', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response('not json', 200, ['Content-Type' => 'application/json'])]);

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
});

it('rejects provider payloads missing the ranking position contract', function () {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['rank' => 4])]);

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
});

it('rejects invalid provider positions', function (mixed $position) {
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['position' => $position])]);

    $keyword = demoKeyword();

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);
    expect($keyword->rankings()->count())->toBe(0);
})->with([
    'negative' => [-1],
    'zero' => [0],
    'float' => [1.5],
    'array' => [[3]],
    'object' => [(object) ['position' => 3]],
    'too large' => [101],
    'nonnumeric string' => ['first'],
]);

it('keeps current ranking unchanged when a later provider check fails', function () {
    $keyword = demoKeyword();
    $keyword->rankings()->create(['position' => 8, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subMinute()]);
    config(['rankwatch.ranking_api_url' => 'https://serp.test/check']);
    Http::fake(['https://serp.test/check*' => Http::response(['message' => 'bad'], 500)]);

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle(app(RankingChecker::class)))->toThrow(RuntimeException::class);

    expect($keyword->refresh()->rankings()->count())->toBe(1)
        ->and($keyword->current_position)->toBe(8);
});

it('orders previous rankings deterministically by checked time and id', function () {
    $keyword = demoKeyword();
    $time = now();

    $keyword->rankings()->create(['position' => 9, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => $time]);
    $keyword->rankings()->create(['position' => 4, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => $time]);

    expect($keyword->refresh()->current_position)->toBe(4)
        ->and($keyword->previous_position)->toBe(9);
});

it('ignores not found observations when calculating best position', function () {
    $keyword = demoKeyword();

    $keyword->rankings()->create(['position' => 14, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subMinutes(2)]);
    $keyword->rankings()->create(['position' => null, 'status' => KeywordRanking::STATUS_NOT_FOUND, 'checked_at' => now()->subMinute()]);
    $keyword->rankings()->create(['position' => 7, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()]);

    expect($keyword->refresh()->best_position)->toBe(7);
});

it('does not calculate numeric change when found becomes not found', function () {
    $keyword = demoKeyword();

    $keyword->rankings()->create(['position' => 8, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subMinute()]);
    $keyword->rankings()->create(['position' => null, 'status' => KeywordRanking::STATUS_NOT_FOUND, 'checked_at' => now()]);

    expect($keyword->refresh()->current_position_label)->toBe('Not found')
        ->and($keyword->position_change)->toBeNull();
});

it('handles not found to found ranking history cleanly', function () {
    $keyword = demoKeyword();

    $keyword->rankings()->create(['position' => null, 'status' => KeywordRanking::STATUS_NOT_FOUND, 'checked_at' => now()->subMinute()]);
    $keyword->rankings()->create(['position' => 6, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()]);

    expect($keyword->refresh()->current_position)->toBe(6)
        ->and($keyword->previous_position_label)->toBe('Not found')
        ->and($keyword->position_change)->toBeNull();
});

it('renders keyword index summaries without loading full ranking history per keyword', function () {
    $user = User::factory()->pro()->create();
    $project = $user->projects()->create(['name' => 'Scale Site', 'url' => 'https://scale.test']);

    foreach (range(1, 80) as $i) {
        $keyword = $project->keywords()->create(['keyword' => "term {$i}", 'country' => 'Indonesia', 'device' => 'desktop']);

        foreach (range(1, 5) as $day) {
            $keyword->rankings()->create([
                'position' => $day === 5 && $i % 10 === 0 ? null : $day + $i,
                'status' => $day === 5 && $i % 10 === 0 ? KeywordRanking::STATUS_NOT_FOUND : KeywordRanking::STATUS_FOUND,
                'checked_at' => now()->subDays(5 - $day),
            ]);
        }
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('keywords.index', $project))
        ->assertOk()
        ->assertSee('Showing')
        ->assertSee('Not found');

    expect(count(DB::getQueryLog()))->toBeLessThan(30);
    DB::disableQueryLog();
});

it('bounds keyword detail chart and paginates ranking history', function () {
    $keyword = demoKeyword();

    foreach (range(1, 45) as $i) {
        $keyword->rankings()->create([
            'position' => $i,
            'status' => KeywordRanking::STATUS_FOUND,
            'checked_at' => now()->subDays(45 - $i),
        ]);
    }

    $this->actingAs($keyword->project->user)
        ->get(route('keywords.show', [$keyword->project, $keyword]))
        ->assertOk()
        ->assertViewHas('chartRankings', fn ($rankings) => $rankings->count() === 30)
        ->assertViewHas('historyRankings', fn ($rankings) => $rankings->count() === 30 && $rankings->total() === 45);
});

it('renders keyword summaries for found not found and no history states', function () {
    $user = User::factory()->pro()->create();
    $project = $user->projects()->create(['name' => 'States', 'url' => 'https://states.test']);
    $found = $project->keywords()->create(['keyword' => 'found keyword', 'country' => 'Indonesia', 'device' => 'desktop']);
    $notFound = $project->keywords()->create(['keyword' => 'missing keyword', 'country' => 'Indonesia', 'device' => 'desktop']);
    $project->keywords()->create(['keyword' => 'new keyword', 'country' => 'Indonesia', 'device' => 'desktop']);

    $found->rankings()->create(['position' => 4, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()]);
    $notFound->rankings()->create(['position' => null, 'status' => KeywordRanking::STATUS_NOT_FOUND, 'checked_at' => now()]);

    $this->actingAs($user)
        ->get(route('keywords.index', $project))
        ->assertOk()
        ->assertSee('found keyword')
        ->assertSee('4')
        ->assertSee('missing keyword')
        ->assertSee('Not found')
        ->assertSee('new keyword');
});

it('calculates seo score from issue severity', function () {
    $issues = collect([
        (object) ['severity' => 'critical'],
        (object) ['severity' => 'high'],
        (object) ['severity' => 'low'],
    ]);

    expect(app(SeoScoreCalculator::class)->calculate($issues))->toBe(84);
});

it('detects basic crawler issues', function () {
    useCrawlerDns(['acme.test' => ['93.184.216.34']]);

    Http::fake([
        'https://acme.test/' => Http::response('<html><head><meta name="robots" content="noindex"></head><body><h1>A</h1><h1>B</h1><img src="/a.jpg"><a href="/missing">Missing</a></body></html>', 200, ['Content-Type' => 'text/html']),
        'https://acme.test/missing' => Http::response('missing', 404),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $crawl = app(SeoCrawler::class)->crawl($project);

    expect($crawl->issues()->pluck('type')->all())->toContain('missing_title', 'robots_noindex', 'missing_image_alt', 'broken_internal_link');
});

it('deduplicates equivalent issues within a single crawl', function () {
    useCrawlerDns(['acme.test' => ['93.184.216.34']]);

    Http::fake([
        'https://acme.test/' => Http::response('<html><head><title>Acme</title><meta name="description" content="Coffee"><link rel="canonical" href="https://acme.test/"></head><body><h1>Acme</h1><img src="/a.jpg"><img src="/b.jpg"></body></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $crawl = app(SeoCrawler::class)->crawl($project);

    expect($crawl->issues()->where('type', 'missing_image_alt')->count())->toBe(1);
});

it('renders the dashboard and project dashboard', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Dashboard');
    $this->actingAs($user)->get(route('projects.show', $project))->assertOk()->assertSee('Acme Coffee');
});

it('uses first completed crawl issues as the current seo state', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]]);

    expect($project->latestCompletedCrawl()->first()->issues()->open()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('projects.issues.index', $project))
        ->assertOk()
        ->assertSee('Missing page title.');
});

it('keeps repeated issues as history without double-counting current state', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]], now()->subDay());
    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]], now());

    expect($project->seoIssues()->count())->toBe(2);
    expect($project->latestCompletedCrawl()->first()->issues()->open()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('projects.report', $project))
        ->assertViewHas('score', 95)
        ->assertSee('Missing page title.');
});

it('drops disappeared issues from current state while preserving old crawl history', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $oldCrawl = completedCrawl($project, [
        ['type' => 'missing_title', 'severity' => 'high', 'message' => 'Missing page title.'],
        ['type' => 'missing_h1', 'severity' => 'medium', 'message' => 'Missing H1 heading.'],
    ], now()->subDay());
    completedCrawl($project, [
        ['type' => 'missing_h1', 'severity' => 'medium', 'message' => 'Missing H1 heading.'],
    ], now());

    expect($oldCrawl->issues()->where('type', 'missing_title')->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('projects.issues.index', $project))
        ->assertSee('Missing H1 heading.')
        ->assertDontSee('Missing page title.');
});

it('shows new later-crawl issues as current state', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [], now()->subDay());
    completedCrawl($project, [[
        'type' => 'missing_meta_description',
        'severity' => 'medium',
        'message' => 'Missing meta description.',
    ]], now());

    $this->actingAs($user)
        ->get(route('projects.issues.index', $project))
        ->assertSee('Missing meta description.');
});

it('treats a resolved issue that reappears in a later crawl as current again', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $firstCrawl = completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]], now()->subDay());
    $firstIssue = $firstCrawl->issues()->first();

    $this->actingAs($user)
        ->patch(route('projects.issues.update', [$project, $firstIssue]))
        ->assertRedirect();

    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]], now());

    expect($firstIssue->refresh()->resolved_at)->not->toBeNull();
    expect($project->latestCompletedCrawl()->first()->issues()->open()->count())->toBe(1);
});

it('scores only open issues from the latest completed crawl', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [
        ['type' => 'missing_title', 'severity' => 'high', 'message' => 'Missing page title.'],
        ['type' => 'http_status', 'severity' => 'critical', 'message' => 'Page returned HTTP 500.'],
    ], now()->subDay());
    $latest = completedCrawl($project, [
        ['type' => 'missing_h1', 'severity' => 'medium', 'message' => 'Missing H1 heading.'],
    ], now());

    expect(app(SeoScoreCalculator::class)->calculateForCrawl($latest))->toBe(97);
});

it('does not let historical duplicate issues progressively reduce current score', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [['type' => 'missing_title', 'severity' => 'high', 'message' => 'Missing page title.']], now()->subDays(2));
    completedCrawl($project, [['type' => 'missing_title', 'severity' => 'high', 'message' => 'Missing page title.']], now()->subDay());
    $latest = completedCrawl($project, [['type' => 'missing_title', 'severity' => 'high', 'message' => 'Missing page title.']], now());

    expect($project->seoIssues()->count())->toBe(3);
    expect(app(SeoScoreCalculator::class)->calculateForCrawl($latest))->toBe(95);
});

it('ignores failed or incomplete crawls when selecting current seo state', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $completed = completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]], now()->subHour());
    $running = $project->crawls()->create(['status' => 'running', 'started_at' => now()]);
    $running->issues()->create([
        'project_id' => $project->id,
        'type' => 'http_status',
        'severity' => 'critical',
        'page_url' => $project->url,
        'message' => 'Page returned HTTP 500.',
    ]);

    expect($project->latestCompletedCrawl()->first()->is($completed))->toBeTrue();
    expect(app(SeoScoreCalculator::class)->calculateForCrawl($project->latestCompletedCrawl()->first()))->toBe(95);
});

it('returns sensible score and issue state when a project has no completed crawl', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertViewHas('score', 100)
        ->assertViewHas('issueCount', 0);

    $this->actingAs($user)
        ->get(route('projects.report', $project))
        ->assertViewHas('score', 100)
        ->assertViewHas('issues', fn ($issues) => $issues->isEmpty());
});

it('keeps dashboard and report scores consistent for current crawl issues', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [[
        'type' => 'missing_h1',
        'severity' => 'medium',
        'message' => 'Missing H1 heading.',
    ]]);

    $this->actingAs($user)->get(route('dashboard'))->assertViewHas('score', 97);
    $this->actingAs($user)->get(route('projects.report', $project))->assertViewHas('score', 97);
});

it('renders dashboard with many keywords without loading full ranking history', function () {
    $user = User::factory()->pro()->create();

    foreach (range(1, 5) as $projectNumber) {
        $project = $user->projects()->create(['name' => "Project {$projectNumber}", 'url' => "https://project{$projectNumber}.test"]);
        completedCrawl($project, [[
            'type' => 'missing_h1',
            'severity' => 'medium',
            'message' => 'Missing H1 heading.',
        ]], now()->subDays($projectNumber));

        foreach (range(1, 25) as $keywordNumber) {
            $keyword = $project->keywords()->create([
                'keyword' => "term {$projectNumber}-{$keywordNumber}",
                'country' => 'Indonesia',
                'device' => 'desktop',
            ]);

            foreach (range(1, 4) as $i) {
                $keyword->rankings()->create([
                    'position' => $i + $keywordNumber,
                    'status' => KeywordRanking::STATUS_FOUND,
                    'checked_at' => now()->subDays(4 - $i),
                ]);
            }
        }
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('125')
        ->assertViewHas('issueCount', 5);

    expect(count(DB::getQueryLog()))->toBeLessThan(25);
    DB::disableQueryLog();
});

it('renders reports from latest completed crawl and bounded keyword summaries', function () {
    $user = User::factory()->pro()->create();
    $project = $user->projects()->create(['name' => 'Report Site', 'url' => 'https://report.test']);
    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Old missing title.',
    ]], now()->subDays(2));
    completedCrawl($project, [[
        'type' => 'missing_h1',
        'severity' => 'medium',
        'message' => 'Current missing H1.',
    ]], now());

    foreach (range(1, 40) as $i) {
        $keyword = $project->keywords()->create(['keyword' => "report {$i}", 'country' => 'Indonesia', 'device' => 'desktop']);

        foreach (range(1, 6) as $rank) {
            $keyword->rankings()->create([
                'position' => $rank + $i,
                'status' => KeywordRanking::STATUS_FOUND,
                'checked_at' => now()->subDays(6 - $rank),
            ]);
        }
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->actingAs($user)
        ->get(route('projects.report', $project))
        ->assertOk()
        ->assertSee('Current missing H1.')
        ->assertDontSee('Old missing title.')
        ->assertViewHas('score', 97);

    expect(count(DB::getQueryLog()))->toBeLessThan(30);
    DB::disableQueryLog();
});

it('filters only current crawl issues by severity', function () {
    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Old high issue.',
    ]], now()->subDay());
    completedCrawl($project, [[
        'type' => 'missing_h1',
        'severity' => 'medium',
        'message' => 'Current medium issue.',
    ]], now());

    $this->actingAs($user)
        ->get(route('projects.issues.index', [$project, 'severity' => 'high']))
        ->assertDontSee('Old high issue.')
        ->assertDontSee('Current medium issue.');

    $this->actingAs($user)
        ->get(route('projects.issues.index', [$project, 'severity' => 'medium']))
        ->assertSee('Current medium issue.');
});

it('keeps cross-user authorization enforced for issue resolution', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = $owner->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $crawl = completedCrawl($project, [[
        'type' => 'missing_title',
        'severity' => 'high',
        'message' => 'Missing page title.',
    ]]);
    $issue = $crawl->issues()->first();

    $this->actingAs($intruder)
        ->patch(route('projects.issues.update', [$project, $issue]))
        ->assertForbidden();

    expect($issue->refresh()->resolved_at)->toBeNull();
});

it('queues manual crawls for project owners without crawling inline', function () {
    Queue::fake();

    $crawler = Mockery::mock(SeoCrawler::class);
    $crawler->shouldNotReceive('crawl');
    app()->instance(SeoCrawler::class, $crawler);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $this->actingAs($user)
        ->post(route('projects.crawl', $project))
        ->assertRedirect()
        ->assertSessionHas('status', 'Crawl queued.');

    Queue::assertPushed(CrawlProjectJob::class, fn (CrawlProjectJob $job) => $job->project->is($project));
});

it('blocks manual crawls for projects owned by another user', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $project = $owner->projects()->create(['name' => 'Owner Site', 'url' => 'https://owner.test']);

    $this->actingAs($intruder)
        ->post(route('projects.crawl', $project))
        ->assertForbidden();

    Queue::assertNothingPushed();
});

it('throttles repeated manual crawl requests per user', function () {
    Queue::fake();

    $user = User::factory()->create();
    $projects = collect(range(1, 6))->map(fn (int $i) => $user->projects()->create([
        'name' => "Site {$i}",
        'url' => "https://site{$i}.test",
    ]));

    $projects->take(5)->each(fn (Project $project) => $this->actingAs($user)
        ->post(route('projects.crawl', $project))
        ->assertRedirect());

    $this->actingAs($user)
        ->post(route('projects.crawl', $projects->last()))
        ->assertStatus(429);

    Queue::assertPushed(CrawlProjectJob::class, 5);
});

it('returns a safe message instead of dispatching duplicate crawl work', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    $this->actingAs($user)->post(route('projects.crawl', $project))->assertSessionHas('status', 'Crawl queued.');
    $this->actingAs($user)->post(route('projects.crawl', $project))->assertSessionHas('status', 'A crawl is already queued or running for this project.');

    Queue::assertPushed(CrawlProjectJob::class, 1);
});

it('prevents overlapping crawl execution for the same project', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $lock = Cache::lock(CrawlProjectJob::overlapKey($project), 30);
    $lock->get();

    $ran = false;
    $job = new CrawlProjectJob($project);
    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });

    $lock->forceRelease();

    expect($ran)->toBeFalse();
});

it('does not block different projects with the crawl overlap lock', function () {
    $user = User::factory()->create();
    $firstProject = $user->projects()->create(['name' => 'One', 'url' => 'https://one.test']);
    $secondProject = $user->projects()->create(['name' => 'Two', 'url' => 'https://two.test']);
    $lock = Cache::lock(CrawlProjectJob::overlapKey($firstProject), 30);
    $lock->get();

    $ran = false;
    $job = new CrawlProjectJob($secondProject);
    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });

    $lock->forceRelease();

    expect($ran)->toBeTrue();
});

it('allows future crawl dispatches after the queued crawl marker expires', function () {
    Queue::fake();
    config(['rankwatch.crawl.lock_ttl' => 1]);

    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeTrue();
    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeFalse();

    $this->travel(2)->seconds();

    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeTrue();
    Queue::assertPushed(CrawlProjectJob::class, 2);
});

it('clears the crawl queued marker if dispatch fails', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $dispatcher = app(Dispatcher::class);
    $failingDispatcher = Mockery::mock(Dispatcher::class);
    $failingDispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('dispatch failed'));
    app()->instance(Dispatcher::class, $failingDispatcher);

    expect(fn () => CrawlProjectJob::dispatchIfAvailable($project))->toThrow(RuntimeException::class);
    expect(Cache::has(CrawlProjectJob::queuedKey($project)))->toBeFalse();

    app()->instance(Dispatcher::class, $dispatcher);
    Queue::fake();

    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeTrue();
    Queue::assertPushed(CrawlProjectJob::class, 1);
});

it('clears the crawl queued marker when the job fails', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    Cache::put(CrawlProjectJob::queuedKey($project), true, 60);
    $crawler = Mockery::mock(SeoCrawler::class);
    $crawler->shouldReceive('crawl')->once()->andThrow(new RuntimeException('crawler failed'));

    expect(fn () => (new CrawlProjectJob($project))->handle($crawler))->toThrow(RuntimeException::class);
    expect(Cache::has(CrawlProjectJob::queuedKey($project)))->toBeFalse();

    Queue::fake();

    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeTrue();
    Queue::assertPushed(CrawlProjectJob::class, 1);
});

it('uses the same crawl availability guard for scheduled and manual dispatches', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);

    expect(CrawlProjectJob::dispatchIfAvailable($project))->toBeTrue();

    $this->actingAs($user)
        ->post(route('projects.crawl', $project))
        ->assertSessionHas('status', 'A crawl is already queued or running for this project.');

    Queue::assertPushed(CrawlProjectJob::class, 1);
});

it('prevents overlapping ranking execution for the same keyword', function () {
    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $lock = Cache::lock(CheckKeywordRankingsJob::overlapKey($keyword), 30);
    $lock->get();

    $ran = false;
    $job = new CheckKeywordRankingsJob($keyword);
    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });

    $lock->forceRelease();

    expect($ran)->toBeFalse();
});

it('does not block different keywords with the ranking overlap lock', function () {
    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $firstKeyword = $project->keywords()->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $secondKeyword = $project->keywords()->create(['keyword' => 'arabica beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $lock = Cache::lock(CheckKeywordRankingsJob::overlapKey($firstKeyword), 30);
    $lock->get();

    $ran = false;
    $job = new CheckKeywordRankingsJob($secondKeyword);
    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });

    $lock->forceRelease();

    expect($ran)->toBeTrue();
});

it('suppresses duplicate ranking dispatches while one is queued', function () {
    Queue::fake();

    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);

    expect(CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toBeTrue();
    expect(CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toBeFalse();

    Queue::assertPushed(CheckKeywordRankingsJob::class, 1);
});

it('allows future ranking dispatches after the queued marker expires', function () {
    Queue::fake();
    config(['rankwatch.ranking_job.lock_ttl' => 1]);

    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);

    expect(CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toBeTrue();
    expect(CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toBeFalse();

    $this->travel(2)->seconds();

    expect(CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toBeTrue();
    Queue::assertPushed(CheckKeywordRankingsJob::class, 2);
});

it('clears the ranking queued marker if dispatch fails', function () {
    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('dispatch failed'));
    app()->instance(Dispatcher::class, $dispatcher);

    expect(fn () => CheckKeywordRankingsJob::dispatchIfAvailable($keyword))->toThrow(RuntimeException::class);
    expect(Cache::has(CheckKeywordRankingsJob::queuedKey($keyword)))->toBeFalse();
});

it('clears the ranking queued marker when the job fails', function () {
    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    Cache::put(CheckKeywordRankingsJob::queuedKey($keyword), true, 60);
    $checker = Mockery::mock(RankingChecker::class);
    $checker->shouldReceive('check')->once()->andThrow(new RuntimeException('provider failed'));

    expect(fn () => (new CheckKeywordRankingsJob($keyword))->handle($checker))->toThrow(RuntimeException::class);
    expect(Cache::has(CheckKeywordRankingsJob::queuedKey($keyword)))->toBeFalse();
});

it('lets the ranking overlap lock expire', function () {
    config(['rankwatch.ranking_job.lock_ttl' => 1]);

    $keyword = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $lock = Cache::lock(CheckKeywordRankingsJob::overlapKey($keyword), 1);
    $lock->get();

    $ran = false;
    $job = new CheckKeywordRankingsJob($keyword);
    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });
    expect($ran)->toBeFalse();

    $this->travel(2)->seconds();

    $job->middleware()[0]->handle($job, function () use (&$ran) {
        $ran = true;
    });

    expect($ran)->toBeTrue();
});

it('uses guarded dispatch paths from the scheduler', function () {
    Queue::fake();

    $project = User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
    $project->keywords()->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $event = rankwatchScheduleEvent();

    $event->run(app());
    $event->run(app());

    Queue::assertPushed(CrawlProjectJob::class, 1);
    Queue::assertPushed(CheckKeywordRankingsJob::class, 1);
});

it('skips overlapping scheduler dispatch runs', function () {
    Queue::fake();

    User::factory()->create()
        ->projects()
        ->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test'])
        ->keywords()
        ->create(['keyword' => 'coffee beans', 'country' => 'Indonesia', 'device' => 'desktop']);
    $event = rankwatchScheduleEvent();
    $lock = Cache::lock($event->mutexName(), $event->expiresAt * 60);
    $lock->get();

    $event->run(app());

    $lock->forceRelease();

    Queue::assertNothingPushed();
});

it('scheduler dispatches more than one chunk of projects and keywords', function () {
    Queue::fake();

    collect(range(1, 101))->each(function (int $i) {
        User::factory()->create()
            ->projects()
            ->create(['name' => "Site {$i}", 'url' => "https://site{$i}.test"])
            ->keywords()
            ->create(['keyword' => "coffee beans {$i}", 'country' => 'Indonesia', 'device' => 'desktop']);
    });

    rankwatchScheduleEvent()->run(app());

    Queue::assertPushed(CrawlProjectJob::class, 101);
    Queue::assertPushed(CheckKeywordRankingsJob::class, 101);
});

it('rejects unsupported crawl URL schemes', function (string $url) {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34']);

    expect($validator->isSafe($url))->toBeFalse();
})->with([
    'file' => ['file:///etc/passwd'],
    'ftp' => ['ftp://example.com/file'],
    'gopher' => ['gopher://example.com'],
    'data' => ['data:text/html;base64,PGgxPkE8L2gxPg=='],
]);

it('rejects obvious local or credentialed crawl URL hosts', function (string $url) {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34']);

    expect($validator->isSafe($url))->toBeFalse();
})->with([
    'localhost' => ['http://localhost/'],
    'single label' => ['http://admin/'],
    'credentials' => ['https://user:pass@example.com/'],
]);

it('rejects unsafe direct IP crawl targets', function (string $url) {
    $validator = new CrawlUrlValidator();

    expect($validator->isSafe($url))->toBeFalse();
})->with([
    'loopback' => ['http://127.0.0.1/'],
    'private 10' => ['http://10.0.0.1/'],
    'private 172' => ['http://172.16.0.1/'],
    'private 192' => ['http://192.168.1.1/'],
    'metadata' => ['http://169.254.169.254/'],
    'ipv6 loopback' => ['http://[::1]/'],
]);

it('accepts safe public crawl URLs', function () {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34']);

    expect($validator->isSafe('https://example.com/'))->toBeTrue();
});

it('rejects hostnames resolving to private IPs', function () {
    $validator = new CrawlUrlValidator(fn () => ['10.0.0.1']);

    expect($validator->isSafe('https://private.test/'))->toBeFalse();
});

it('rejects mixed DNS results containing an unsafe address', function () {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34', '127.0.0.1']);

    expect($validator->isSafe('https://mixed.test/'))->toBeFalse();
});

it('rejects unsupported crawl ports', function () {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34']);

    expect($validator->isSafe('https://example.com:444/'))->toBeFalse();
});

it('rejects redirects to private IPs before requesting them', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);

    Http::fake([
        'https://public.test/' => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    Http::assertSentCount(1);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '127.0.0.1'));
    expect($project->seoIssues()->pluck('type')->all())->toContain('http_status');
});

it('enforces the redirect limit', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);
    config(['rankwatch.crawl.max_redirects' => 1]);

    Http::fake([
        'https://public.test/' => Http::response('', 302, ['Location' => '/two']),
        'https://public.test/two' => Http::response('', 302, ['Location' => '/three']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    Http::assertSentCount(2);
    expect($project->seoIssues()->pluck('type')->all())->toContain('http_status');
});

it('does not parse non-html crawler responses', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);

    Http::fake([
        'https://public.test/' => Http::response('%PDF', 200, ['Content-Type' => 'application/pdf']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    $types = $project->seoIssues()->pluck('type')->all();
    expect($types)->toContain('non_html')
        ->not->toContain('missing_title');
});

it('aborts parsing oversized crawler responses', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);
    config(['rankwatch.crawl.max_body_bytes' => 10]);

    Http::fake([
        'https://public.test/' => Http::response(str_repeat('A', 20), 200, ['Content-Type' => 'text/html']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    $types = $project->seoIssues()->pluck('type')->all();
    expect($types)->toContain('body_too_large')
        ->not->toContain('missing_title');
});

it('enforces the max links per page limit', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);
    config([
        'rankwatch.crawl.max_pages' => 1,
        'rankwatch.crawl.max_links_per_page' => 2,
        'rankwatch.crawl.max_requests' => 10,
    ]);

    Http::fake([
        'https://public.test/' => Http::response('<html><body><a href="/a">A</a><a href="/b">B</a><a href="/c">C</a></body></html>', 200, ['Content-Type' => 'text/html']),
        'https://public.test/*' => Http::response('missing', 404),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    Http::assertSentCount(3);
});

it('enforces the total crawl request budget', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);
    config([
        'rankwatch.crawl.max_pages' => 1,
        'rankwatch.crawl.max_links_per_page' => 10,
        'rankwatch.crawl.max_requests' => 2,
    ]);

    Http::fake([
        'https://public.test/' => Http::response('<html><body><a href="/a">A</a><a href="/b">B</a><a href="/c">C</a></body></html>', 200, ['Content-Type' => 'text/html']),
        'https://public.test/*' => Http::response('missing', 404),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    Http::assertSentCount(2);
});

it('deduplicates normalized internal links before checking them', function () {
    useCrawlerDns(['public.test' => ['93.184.216.34']]);
    config([
        'rankwatch.crawl.max_pages' => 1,
        'rankwatch.crawl.max_links_per_page' => 10,
        'rankwatch.crawl.max_requests' => 10,
    ]);

    Http::fake([
        'https://public.test/' => Http::response('<html><body><a href="/a#one">A</a><a href="/a#two">A2</a><a href="/a">A3</a></body></html>', 200, ['Content-Type' => 'text/html']),
        'https://public.test/a' => Http::response('ok', 200, ['Content-Type' => 'text/html']),
    ]);

    $user = User::factory()->create();
    $project = $user->projects()->create(['name' => 'Public Site', 'url' => 'https://public.test']);

    app(SeoCrawler::class)->crawl($project);

    Http::assertSentCount(2);
});
