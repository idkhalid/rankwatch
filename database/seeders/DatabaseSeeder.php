<?php

namespace Database\Seeders;

use App\Models\SeoIssue;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@rankwatch.test',
        ]);

        $project = $user->projects()->create([
            'name' => 'Acme Coffee',
            'url' => 'https://example.com',
            'description' => 'Demo coffee shop SEO project.',
        ]);

        $keywords = [
            'best coffee beans jakarta',
            'arabica coffee indonesia',
            'coffee subscription jakarta',
            'fresh roasted coffee',
            'single origin coffee',
            'coffee shop seo',
            'manual brew beans',
            'espresso beans online',
            'indonesian coffee roaster',
            'coffee gift box',
            'buy coffee beans online',
            'specialty coffee jakarta',
        ];

        foreach ($keywords as $index => $term) {
            $keyword = $project->keywords()->create([
                'keyword' => $term,
                'target_url' => 'https://example.com',
                'country' => 'Indonesia',
                'device' => $index % 3 === 0 ? 'mobile' : 'desktop',
            ]);

            for ($day = 10; $day >= 0; $day--) {
                $keyword->rankings()->create([
                    'position' => max(1, 18 + $index - $day + random_int(-2, 2)),
                    'checked_at' => now()->subDays($day),
                ]);
            }
        }

        $crawl = $project->crawls()->create([
            'status' => 'completed',
            'pages_crawled' => 6,
            'issues_found' => 5,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinutes(3),
        ]);

        foreach ([
            ['missing_meta_description', 'medium', 'Missing meta description.'],
            ['missing_h1', 'medium', 'Missing H1 heading.'],
            ['missing_image_alt', 'low', 'Image is missing alt text.'],
            ['missing_canonical', 'low', 'Missing canonical URL.'],
            ['title_too_long', 'medium', 'Page title is longer than 60 characters.'],
        ] as [$type, $severity, $message]) {
            $crawl->issues()->create([
                'project_id' => $project->id,
                'type' => $type,
                'severity' => $severity,
                'page_url' => 'https://example.com',
                'message' => $message,
            ]);
        }
    }
}
