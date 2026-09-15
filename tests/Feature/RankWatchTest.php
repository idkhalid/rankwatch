<?php

use App\Jobs\CheckKeywordRankingsJob;
use App\Jobs\CrawlProjectJob;
use App\Models\Crawl;
use App\Models\Project;
use App\Models\User;
use App\Services\CrawlUrlValidator;
use App\Services\SeoCrawler;
use App\Services\SeoScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

it('rejects unsupported crawl URL schemes', function (string $url) {
    $validator = new CrawlUrlValidator(fn () => ['93.184.216.34']);

    expect($validator->isSafe($url))->toBeFalse();
})->with([
    'file' => ['file:///etc/passwd'],
    'ftp' => ['ftp://example.com/file'],
    'gopher' => ['gopher://example.com'],
    'data' => ['data:text/html;base64,PGgxPkE8L2gxPg=='],
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
