<?php

namespace App\Services;

use App\Models\Crawl;
use App\Models\Project;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;

class SeoCrawler
{
    public function __construct(private CrawlUrlValidator $urls)
    {
    }

    public function crawl(Project $project): Crawl
    {
        $project->loadMissing('user');
        $maxPages = min((int) config('rankwatch.crawl.max_pages'), (int) $project->user->planLimit('crawl_pages'));

        $crawl = $project->crawls()->create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $startUrl = $this->urls->normalize($project->url);
        $pages = $startUrl ? [$startUrl] : [];
        $visited = [];
        $checkedLinks = [];
        $requests = 0;

        for ($i = 0; $i < count($pages); $i++) {
            $url = $pages[$i];

            if (count($visited) >= $maxPages || isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;
            $started = microtime(true);
            try {
                $response = $this->get($url, $requests);
            } catch (\Throwable $e) {
                $this->issue($crawl, 'http_status', 'critical', $url, 'Page could not be reached.');
                continue;
            }

            $ms = (int) ((microtime(true) - $started) * 1000);

            if (! str_starts_with($url, 'https://')) {
                $this->issue($crawl, 'non_https', 'high', $url, 'Page is not served over HTTPS.');
            }

            if (! $response->ok()) {
                $this->issue($crawl, 'http_status', 'critical', $url, "Page returned HTTP {$response->status()}.");
                continue;
            }

            if (! $this->isHtml($response)) {
                $this->issue($crawl, 'non_html', 'low', $url, 'Page is not HTML and was skipped.');
                continue;
            }

            $body = $response->body();
            if (strlen($body) > config('rankwatch.crawl.max_body_bytes')) {
                $this->issue($crawl, 'body_too_large', 'medium', $url, 'Page body is too large to crawl safely.');
                continue;
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML($body);
            $xpath = new \DOMXPath($dom);

            $title = trim($xpath->evaluate('string(//title)'));
            $description = trim($xpath->evaluate('string(//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]/@content)'));
            $canonical = trim($xpath->evaluate('string(//link[translate(@rel,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="canonical"]/@href)'));
            $robots = trim($xpath->evaluate('string(//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="robots"]/@content)'));
            $h1Count = $xpath->query('//h1')->length;

            if ($title === '') {
                $this->issue($crawl, 'missing_title', 'high', $url, 'Missing page title.');
            } elseif (mb_strlen($title) > 60) {
                $this->issue($crawl, 'title_too_long', 'medium', $url, 'Page title is longer than 60 characters.');
            }

            if ($description === '') {
                $this->issue($crawl, 'missing_meta_description', 'medium', $url, 'Missing meta description.');
            }

            if ($canonical === '') {
                $this->issue($crawl, 'missing_canonical', 'low', $url, 'Missing canonical URL.');
            }

            if (str_contains(strtolower($robots), 'noindex')) {
                $this->issue($crawl, 'robots_noindex', 'high', $url, 'Robots meta blocks indexing.');
            }

            if ($h1Count === 0) {
                $this->issue($crawl, 'missing_h1', 'medium', $url, 'Missing H1 heading.');
            } elseif ($h1Count > 1) {
                $this->issue($crawl, 'multiple_h1', 'low', $url, 'Page has multiple H1 headings.');
            }

            foreach ($xpath->query('//img[not(@alt) or @alt=""]') as $img) {
                $this->issue($crawl, 'missing_image_alt', 'low', $url, 'Image is missing alt text.');
            }

            if ($ms > 2500) {
                $this->issue($crawl, 'slow_response', 'medium', $url, "Page responded in {$ms}ms.");
            }

            $linksInspected = 0;

            foreach ($xpath->query('//a[@href]') as $link) {
                if ($linksInspected >= config('rankwatch.crawl.max_links_per_page')) {
                    break;
                }

                $linksInspected++;
                $href = $this->internalUrl($project, $link->getAttribute('href'), $url);

                if (! $href) {
                    continue;
                }

                if (count($pages) < $maxPages && ! isset($visited[$href])) {
                    $pages[] = $href;
                }

                if (isset($checkedLinks[$href])) {
                    continue;
                }

                $checkedLinks[$href] = true;

                try {
                    if ($this->get($href, $requests)->failed()) {
                        $this->issue($crawl, 'broken_internal_link', 'high', $url, "Broken internal link: {$href}");
                    }
                } catch (\Throwable $e) {
                    $this->issue($crawl, 'broken_internal_link', 'high', $url, "Broken internal link: {$href}");
                }
            }
        }

        $crawl->update([
            'status' => 'completed',
            'pages_crawled' => count($visited),
            'issues_found' => $crawl->issues()->count(),
            'finished_at' => now(),
        ]);

        return $crawl->refresh();
    }

    private function get(string $url, int &$requests): Response
    {
        $url = $this->urls->normalize($url);

        if (! $url || ! $this->urls->isSafe($url)) {
            throw new \RuntimeException('Unsafe crawl URL.');
        }

        $redirects = 0;

        while (true) {
            if ($requests >= config('rankwatch.crawl.max_requests')) {
                throw new \RuntimeException('Crawl request budget exceeded.');
            }

            $requests++;

            $response = Http::connectTimeout(config('rankwatch.crawl.connect_timeout'))
                ->timeout(config('rankwatch.crawl.timeout'))
                ->withOptions([
                    'allow_redirects' => false,
                    'on_headers' => function (ResponseInterface $response): void {
                        $length = $response->getHeaderLine('Content-Length');

                        if ($length !== '' && (int) $length > config('rankwatch.crawl.max_body_bytes')) {
                            throw new \RuntimeException('Response body is too large.');
                        }
                    },
                ])
                ->get($url);

            if (! $response->redirect()) {
                return $response;
            }

            if ($redirects >= config('rankwatch.crawl.max_redirects')) {
                throw new \RuntimeException('Too many redirects.');
            }

            $location = $response->header('Location');
            $next = $location ? $this->urls->normalize($location, $url) : null;

            if (! $next || ! $this->urls->isSafe($next)) {
                throw new \RuntimeException('Unsafe redirect URL.');
            }

            $url = $next;
            $redirects++;
        }
    }

    private function isHtml(Response $response): bool
    {
        $contentType = strtolower(strtok($response->header('Content-Type', ''), ';') ?: '');

        return in_array($contentType, ['text/html', 'application/xhtml+xml'], true);
    }

    private function internalUrl(Project $project, string $href, string $baseUrl): ?string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#')) {
            return null;
        }

        $url = $this->urls->normalize($href, $baseUrl);

        return $url && $this->urls->sameHost($url, $project->domain) ? $url : null;
    }

    private function issue(Crawl $crawl, string $type, string $severity, string $url, string $message): void
    {
        $crawl->issues()->firstOrCreate([
            'type' => $type,
            'page_url' => $url,
            'message' => $message,
        ], [
            'project_id' => $crawl->project_id,
            'severity' => $severity,
        ]);
    }
}
