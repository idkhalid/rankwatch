<?php

use App\Models\User;
use App\Services\CrawlUrlValidator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function projectUrlPublicDns(): void
{
    app()->bind(CrawlUrlValidator::class, fn () => new CrawlUrlValidator(fn () => ['93.184.216.34']));
}

it('canonicalizes project root urls on create', function (string $input, string $expected) {
    projectUrlPublicDns();

    $user = User::factory()->pro()->create();

    $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Canonical',
        'url' => $input,
    ])->assertRedirect();

    expect($user->projects()->first()->url)->toBe($expected);
})->with([
    ['https://EXAMPLE.com', 'https://example.com/'],
    ['https://example.com:443/', 'https://example.com/'],
    ['http://EXAMPLE.com:80/', 'http://example.com/'],
]);

it('rejects unsupported project url components', function (string $url, string $message) {
    projectUrlPublicDns();

    $user = User::factory()->pro()->create();

    $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Invalid',
        'url' => $url,
    ])->assertSessionHasErrors(['url' => $message]);
})->with([
    ['https://example.com/#about', 'Fragments are not supported for project URLs.'],
    ['https://user:pass@example.com/', 'Credentials are not allowed in project URLs.'],
    ['https://example.com/?utm=test', 'Query strings are not supported for project URLs.'],
    ['https://example.com/blog', 'Project URL must represent a website root.'],
]);

it('rejects same-user canonical duplicate projects but allows different users', function () {
    projectUrlPublicDns();

    $firstUser = User::factory()->pro()->create();
    $secondUser = User::factory()->pro()->create();

    $this->actingAs($firstUser)->post(route('projects.store'), [
        'name' => 'First',
        'url' => 'https://EXAMPLE.com',
    ])->assertRedirect();

    $this->actingAs($firstUser)->post(route('projects.store'), [
        'name' => 'Duplicate',
        'url' => 'https://example.com:443/',
    ])->assertSessionHasErrors(['url' => 'This website is already being monitored.']);

    $this->actingAs($secondUser)->post(route('projects.store'), [
        'name' => 'Allowed',
        'url' => 'https://example.com/',
    ])->assertRedirect();

    expect($firstUser->projects()->count())->toBe(1)
        ->and($secondUser->projects()->count())->toBe(1);
});

it('normalizes project updates and ignores itself for uniqueness', function () {
    projectUrlPublicDns();

    $user = User::factory()->pro()->create();
    $project = $user->projects()->create(['name' => 'Site', 'url' => 'https://example.com/']);

    $this->actingAs($user)->patch(route('projects.update', $project), [
        'name' => 'Site',
        'url' => 'https://EXAMPLE.com:443/',
    ])->assertRedirect();

    expect($project->refresh()->url)->toBe('https://example.com/');
});

it('rejects project updates to another same-user canonical url', function () {
    projectUrlPublicDns();

    $user = User::factory()->pro()->create();
    $first = $user->projects()->create(['name' => 'First', 'url' => 'https://first.test/']);
    $second = $user->projects()->create(['name' => 'Second', 'url' => 'https://second.test/']);

    $this->actingAs($user)->patch(route('projects.update', $second), [
        'name' => 'Second',
        'url' => 'https://FIRST.test:443/',
    ])->assertSessionHasErrors(['url' => 'This website is already being monitored.']);

    expect($second->refresh()->url)->toBe('https://second.test/');
});

it('enforces canonical uniqueness at the database level', function () {
    $user = User::factory()->create();

    DB::table('projects')->insert([
        'user_id' => $user->id,
        'name' => 'First',
        'slug' => 'first',
        'url' => 'https://example.com/',
        'domain' => 'example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('projects')->insert([
        'user_id' => $user->id,
        'name' => 'Second',
        'slug' => 'second',
        'url' => 'https://example.com/',
        'domain' => 'example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('keeps fresh install database setup explicit and mysql first', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true);
    $postCreate = implode("\n", $composer['scripts']['post-create-project-cmd'] ?? []);
    $envExample = file_get_contents(base_path('.env.example'));
    $readme = file_get_contents(base_path('README.md'));

    expect($postCreate)->not->toContain('database.sqlite')
        ->and($postCreate)->not->toContain('artisan migrate')
        ->and($envExample)->toContain('DB_CONNECTION=mysql')
        ->and($envExample)->toContain('DB_DATABASE=rankwatch')
        ->and($readme)->toContain('Composer does not create the database or run migrations automatically.');
});
