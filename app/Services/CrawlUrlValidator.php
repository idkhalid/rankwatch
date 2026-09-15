<?php

namespace App\Services;

class CrawlUrlValidator
{
    /** @var callable(string): array<int, string> */
    private $resolver;

    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver ?? fn (string $host) => $this->resolveHost($host);
    }

    public function normalize(string $url, ?string $baseUrl = null): ?string
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return null;
        }

        if ($baseUrl && str_starts_with($url, '//')) {
            $base = parse_url($baseUrl);
            $url = ($base['scheme'] ?? 'https').':'.$url;
        } elseif ($baseUrl && ! parse_url($url, PHP_URL_SCHEME)) {
            $url = $this->resolveRelativeUrl($baseUrl, $url);
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $port = $parts['port'] ?? null;
        if ($port !== null && $this->isDefaultPort($scheme, $port)) {
            $port = null;
        }

        $path = $parts['path'] ?? '/';
        $path = $path === '' ? '/' : $path;
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $authorityHost = str_contains($host, ':') ? "[{$host}]" : $host;

        return $scheme.'://'.$authorityHost.($port ? ':'.$port : '').$path.$query;
    }

    public function isSafe(string $url): bool
    {
        $url = $this->normalize($url);

        if (! $url) {
            return false;
        }

        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (! in_array($port, config("rankwatch.crawl.allowed_ports.{$scheme}", []), true)) {
            return false;
        }

        if ($this->isObviousLocalHost($host)) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolver)($host);

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if ($this->isUnsafeIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function sameHost(string $url, string $host): bool
    {
        return strtolower(parse_url($url, PHP_URL_HOST) ?: '') === strtolower($host);
    }

    private function resolveRelativeUrl(string $baseUrl, string $url): string
    {
        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($url, '?')) {
            $path = $base['path'] ?? '/';
            return "{$scheme}://{$host}{$port}{$path}{$url}";
        }

        if (str_starts_with($url, '/')) {
            return "{$scheme}://{$host}{$port}{$url}";
        }

        $basePath = $base['path'] ?? '/';
        $directory = rtrim(str_ends_with($basePath, '/') ? $basePath : dirname($basePath), '/');

        return "{$scheme}://{$host}{$port}".($directory === '' ? '' : $directory).'/'.$url;
    }

    private function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    }

    private function isObviousLocalHost(string $host): bool
    {
        return $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || (! str_contains($host, '.') && ! filter_var($host, FILTER_VALIDATE_IP));
    }

    /**
     * @return array<int, string>
     */
    private function resolveHost(string $host): array
    {
        $records = dns_get_record($host, DNS_A + DNS_AAAA);
        $ips = [];

        foreach ($records ?: [] as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }

            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }

    private function isUnsafeIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4InRanges($ip, [
                ['0.0.0.0', 8],
                ['10.0.0.0', 8],
                ['100.64.0.0', 10],
                ['127.0.0.0', 8],
                ['169.254.0.0', 16],
                ['172.16.0.0', 12],
                ['192.0.0.0', 24],
                ['192.0.2.0', 24],
                ['192.168.0.0', 16],
                ['198.18.0.0', 15],
                ['198.51.100.0', 24],
                ['203.0.113.0', 24],
                ['224.0.0.0', 4],
                ['240.0.0.0', 4],
            ]);
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        $packed = inet_pton($ip);
        $hex = bin2hex($packed);

        if (str_starts_with($hex, '00000000000000000000ffff')) {
            return $this->isUnsafeIp(inet_ntop(substr($packed, 12)));
        }

        return $ip === '::'
            || $ip === '::1'
            || str_starts_with($hex, 'fc')
            || str_starts_with($hex, 'fd')
            || in_array(substr($hex, 0, 3), ['fe8', 'fe9', 'fea', 'feb'], true)
            || str_starts_with($hex, 'ff');
    }

    /**
     * @param array<int, array{0: string, 1: int}> $ranges
     */
    private function ipv4InRanges(string $ip, array $ranges): bool
    {
        $value = ip2long($ip);

        foreach ($ranges as [$range, $bits]) {
            $rangeValue = ip2long($range);
            $mask = -1 << (32 - $bits);

            if (($value & $mask) === ($rangeValue & $mask)) {
                return true;
            }
        }

        return false;
    }
}
