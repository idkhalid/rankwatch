<?php

use App\Jobs\CheckKeywordRankingsJob;
use App\Jobs\CrawlProjectJob;
use App\Models\Keyword;
use App\Models\KeywordRanking;
use App\Models\Project;
use App\Models\User;
use App\Notifications\CriticalIssuesFound;
use App\Notifications\CrawlCompleted;
use App\Notifications\KeywordDropped;
use App\Services\CrawlUrlValidator;
use App\Services\RankingChecker;
use App\Services\SeoCrawler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

function planTestPublicDns(array $records = []): void
{
    app()->bind(CrawlUrlValidator::class, fn () => new CrawlUrlValidator(
        fn (string $host) => $records[strtolower($host)] ?? ['93.184.216.34']
    ));
}

function planTestScheduleEvent()
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'rankwatch-daily-monitoring');
}

function planTestProject(User $user): Project
{
    return $user->projects()->create(['name' => 'Acme Coffee', 'url' => 'https://acme.test']);
}

function planTestKeyword(Project $project): Keyword
{
    return $project->keywords()->create([
        'keyword' => 'coffee beans',
        'country' => 'Indonesia',
        'device' => 'desktop',
    ]);
}

it('defaults new users and registrations to the free plan', function () {
    expect(User::factory()->create()->plan)->toBe(User::PLAN_FREE);

    $this->post('/register', [
        'name' => 'Plan User',
        'email' => 'plan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'plan' => User::PLAN_PRO,
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::where('email', 'plan@example.com')->first()->plan)->toBe(User::PLAN_FREE);
});

it('does not let users change plan through profile updates', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated',
        'email' => $user->email,
        'plan' => User::PLAN_PRO,
    ])->assertRedirect(route('profile.edit', absolute: false));

    expect($user->refresh()->plan)->toBe(User::PLAN_FREE);
});

it('enforces free and pro project limits on project creation', function () {
    planTestPublicDns();

    $free = User::factory()->create();

    $this->actingAs($free)->post(route('projects.store'), [
        'name' => 'First',
        'url' => 'https://first.test',
    ])->assertRedirect();

    $this->actingAs($free)->post(route('projects.store'), [
        'name' => 'Second',
        'url' => 'https://second.test',
        'plan' => User::PLAN_PRO,
    ])->assertSessionHasErrors('plan');

    expect($free->projects()->count())->toBe(1)
        ->and($free->refresh()->plan)->toBe(User::PLAN_FREE);

    $pro = User::factory()->pro()->create();

    foreach (range(1, 10) as $i) {
        $this->actingAs($pro)->post(route('projects.store'), [
            'name' => "Pro {$i}",
            'url' => "https://pro{$i}.test",
        ])->assertRedirect();
    }

    expect($pro->projects()->count())->toBe(10);
});

it('enforces free and pro keyword limits on keyword creation', function () {
    $freeProject = planTestProject(User::factory()->create());

    foreach (range(1, 9) as $i) {
        $freeProject->keywords()->create(['keyword' => "free {$i}", 'country' => 'Indonesia', 'device' => 'desktop']);
    }

    $this->actingAs($freeProject->user)->post(route('keywords.store', $freeProject), [
        'keyword' => 'free 10',
        'country' => 'Indonesia',
        'device' => 'desktop',
        'plan' => User::PLAN_PRO,
    ])->assertRedirect();

    $this->actingAs($freeProject->user)->post(route('keywords.store', $freeProject), [
        'keyword' => 'free 11',
        'country' => 'Indonesia',
        'device' => 'desktop',
    ])->assertSessionHasErrors('plan');

    expect($freeProject->keywords()->count())->toBe(10)
        ->and($freeProject->user->refresh()->plan)->toBe(User::PLAN_FREE);

    $proProject = planTestProject(User::factory()->pro()->create());
    foreach (range(1, 99) as $i) {
        $proProject->keywords()->create(['keyword' => "pro {$i}", 'country' => 'Indonesia', 'device' => 'desktop']);
    }

    $this->actingAs($proProject->user)->post(route('keywords.store', $proProject), [
        'keyword' => 'pro 100',
        'country' => 'Indonesia',
        'device' => 'desktop',
    ])->assertRedirect();

    expect($proProject->keywords()->count())->toBe(100);
});

it('applies plan crawl page limits without exceeding the system safety ceiling', function (string $state, int $systemLimit, int $expectedPages) {
    planTestPublicDns(['public.test' => ['93.184.216.34']]);
    config([
        'rankwatch.crawl.max_pages' => $systemLimit,
        'rankwatch.crawl.max_links_per_page' => 150,
        'rankwatch.crawl.max_requests' => 200,
    ]);

    $links = collect(range(1, 120))->map(fn (int $i) => '<a href="/'.$i.'">'.$i.'</a>')->implode('');
    Http::fake([
        'https://public.test/' => Http::response('<html><head><title>Home</title><meta name="description" content="A"><link rel="canonical" href="https://public.test/"></head><body><h1>Home</h1>'.$links.'</body></html>', 200, ['Content-Type' => 'text/html']),
        'https://public.test/*' => Http::response('<html><head><title>Page</title><meta name="description" content="A"><link rel="canonical" href="https://public.test/"></head><body><h1>Page</h1></body></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $factory = User::factory();
    $user = $state === 'pro' ? $factory->pro()->create() : $factory->create();
    $project = $user->projects()->create(['name' => 'Crawl', 'url' => 'https://public.test']);

    $crawl = app(SeoCrawler::class)->crawl($project);

    expect($crawl->pages_crawled)->toBe($expectedPages);
})->with([
    'free plan' => ['free', 100, 10],
    'pro plan' => ['pro', 120, 100],
    'system ceiling wins' => ['pro', 8, 8],
]);

it('has default crawl ceilings that support free and pro page allowances', function () {
    $free = User::factory()->create();
    $pro = User::factory()->pro()->create();

    expect(min(config('rankwatch.crawl.max_pages'), $free->planLimit('crawl_pages')))->toBe(10)
        ->and(min(config('rankwatch.crawl.max_pages'), $pro->planLimit('crawl_pages')))->toBe(100)
        ->and(config('rankwatch.crawl.max_requests'))->toBeGreaterThanOrEqual(199)
        ->and(config('rankwatch.crawl.max_requests'))->toBeLessThanOrEqual(250)
        ->and(config('rankwatch.crawl.max_requests'))->toBeInt();
});

it('falls back safely for unknown persisted plans', function () {
    $user = User::factory()->create();
    $user->forceFill(['plan' => 'enterprise'])->save();

    expect($user->fresh()->planLimit('projects'))->toBe(config('plans.free.projects'))
        ->and($user->fresh()->planLimit('crawl_pages'))->toBe(config('plans.free.crawl_pages'))
        ->and($user->fresh()->canUseFeature('keyword_drop_notifications'))->toBeFalse()
        ->and($user->fresh()->canUseFeature('crawl_completed_notifications'))->toBeFalse();
});

it('keeps public pricing copy aligned with free and pro foundation', function () {
    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('Free')
        ->assertSee('$0')
        ->assertSee('Pro')
        ->assertSee('Coming soon')
        ->assertSee('Up to 10 pages per crawl')
        ->assertSee('Up to 100 pages per crawl')
        ->assertDontSee('365 days history')
        ->assertDontSee('Starter')
        ->assertDontSee('$19')
        ->assertDontSee('Freelancer')
        ->assertDontSee('$39')
        ->assertDontSee('Agency')
        ->assertDontSee('$79');

    $this->get(route('marketing.home'))
        ->assertOk()
        ->assertSee('Track rankings. Catch SEO issues. See what changed.')
        ->assertSee('Technical SEO Crawling')
        ->assertSee('Free')
        ->assertSee('$0')
        ->assertSee('1 website')
        ->assertSee('10 keywords per site')
        ->assertSee('Up to 10 pages per crawl')
        ->assertSee('Weekly automatic monitoring')
        ->assertSee('Pro')
        ->assertSee('Coming soon')
        ->assertSee('Up to 100 pages per crawl')
        ->assertDontSee('unlimited')
        ->assertDontSee('Starter')
        ->assertDontSee('$19')
        ->assertDontSee('Freelancer')
        ->assertDontSee('$39')
        ->assertDontSee('Agency')
        ->assertDontSee('$79');
});

it('keeps manual crawl available for free users and preserves duplicate dispatch protection', function () {
    Queue::fake();

    $project = planTestProject(User::factory()->create());

    $this->actingAs($project->user)->post(route('projects.crawl', $project))->assertSessionHas('status', 'Crawl queued.');
    $this->actingAs($project->user)->post(route('projects.crawl', $project))->assertSessionHas('status', 'A crawl is already queued or running for this project.');

    Queue::assertPushed(CrawlProjectJob::class, 1);
});

it('uses plan frequency for scheduled crawls', function () {
    Queue::fake();

    $freeRecent = planTestProject(User::factory()->create());
    $freeRecent->crawls()->create(['status' => 'completed', 'started_at' => now()->subDay(), 'finished_at' => now()->subDay()]);

    $freeOld = planTestProject(User::factory()->create());
    $freeOld->crawls()->create(['status' => 'completed', 'started_at' => now()->subDays(8), 'finished_at' => now()->subDays(8)]);

    $proRecent = planTestProject(User::factory()->pro()->create());
    $proRecent->crawls()->create(['status' => 'completed', 'started_at' => now()->subDay(), 'finished_at' => now()->subDay()]);

    planTestScheduleEvent()->run(app());

    Queue::assertPushed(CrawlProjectJob::class, fn (CrawlProjectJob $job) => $job->project->is($freeOld));
    Queue::assertPushed(CrawlProjectJob::class, fn (CrawlProjectJob $job) => $job->project->is($proRecent));
    Queue::assertNotPushed(CrawlProjectJob::class, fn (CrawlProjectJob $job) => $job->project->is($freeRecent));
});

it('uses plan frequency for scheduled ranking checks', function () {
    Queue::fake();

    $freeRecent = planTestKeyword(planTestProject(User::factory()->create()));
    $freeRecent->rankings()->create(['position' => 5, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subDay()]);

    $freeOld = planTestKeyword(planTestProject(User::factory()->create()));
    $freeOld->rankings()->create(['position' => 5, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subDays(8)]);

    $proRecent = planTestKeyword(planTestProject(User::factory()->pro()->create()));
    $proRecent->rankings()->create(['position' => 5, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subDay()]);

    planTestScheduleEvent()->run(app());

    Queue::assertPushed(CheckKeywordRankingsJob::class, fn (CheckKeywordRankingsJob $job) => $job->keyword->is($freeOld));
    Queue::assertPushed(CheckKeywordRankingsJob::class, fn (CheckKeywordRankingsJob $job) => $job->keyword->is($proRecent));
    Queue::assertNotPushed(CheckKeywordRankingsJob::class, fn (CheckKeywordRankingsJob $job) => $job->keyword->is($freeRecent));
});

it('gates crawl notifications by plan and notification preference', function () {
    Notification::fake();

    $freeProject = planTestProject(User::factory()->create());
    $proProject = planTestProject(User::factory()->pro()->create());
    $mutedProProject = planTestProject(User::factory()->pro()->create(['email_notifications' => false]));

    foreach ([$freeProject, $proProject, $mutedProProject] as $project) {
        $crawl = $project->crawls()->create([
            'status' => 'completed',
            'pages_crawled' => 1,
            'issues_found' => 1,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        $crawl->issues()->create([
            'project_id' => $project->id,
            'type' => 'http_status',
            'severity' => 'critical',
            'page_url' => $project->url,
            'message' => 'Page returned HTTP 500.',
        ]);

        $crawler = Mockery::mock(SeoCrawler::class);
        $crawler->shouldReceive('crawl')->once()->with(Mockery::type(Project::class))->andReturn($crawl);
        (new CrawlProjectJob($project))->handle($crawler);
    }

    Notification::assertNotSentTo($freeProject->user, CrawlCompleted::class);
    Notification::assertSentTo($freeProject->user, CriticalIssuesFound::class);
    Notification::assertSentTo($proProject->user, CrawlCompleted::class);
    Notification::assertSentTo($proProject->user, CriticalIssuesFound::class);
    Notification::assertNotSentTo($mutedProProject->user, CrawlCompleted::class);
    Notification::assertNotSentTo($mutedProProject->user, CriticalIssuesFound::class);
});

it('gates keyword drop notifications by plan and notification preference', function () {
    Notification::fake();

    $freeKeyword = planTestKeyword(planTestProject(User::factory()->create()));
    $proKeyword = planTestKeyword(planTestProject(User::factory()->pro()->create()));
    $mutedProKeyword = planTestKeyword(planTestProject(User::factory()->pro()->create(['email_notifications' => false])));

    foreach ([$freeKeyword, $proKeyword, $mutedProKeyword] as $keyword) {
        $keyword->rankings()->create(['position' => 3, 'status' => KeywordRanking::STATUS_FOUND, 'checked_at' => now()->subDay()]);

        $checker = Mockery::mock(RankingChecker::class);
        $checker->shouldReceive('check')->once()->with(Mockery::type(Keyword::class))->andReturn([
            'position' => 12,
            'status' => KeywordRanking::STATUS_FOUND,
        ]);
        (new CheckKeywordRankingsJob($keyword))->handle($checker);
    }

    Notification::assertNotSentTo($freeKeyword->project->user, KeywordDropped::class);
    Notification::assertSentTo($proKeyword->project->user, KeywordDropped::class);
    Notification::assertNotSentTo($mutedProKeyword->project->user, KeywordDropped::class);
});
