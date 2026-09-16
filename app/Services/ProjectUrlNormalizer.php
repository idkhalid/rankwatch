<?php

namespace App\Services;

use InvalidArgumentException;

class ProjectUrlNormalizer
{
    public function normalize(string $url): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F\s]/', $url)) {
            throw new InvalidArgumentException('Enter a valid website URL.');
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            throw new InvalidArgumentException('Enter a valid website URL.');
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException('Project URL must use HTTP or HTTPS.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('Credentials are not allowed in project URLs.');
        }

        if (array_key_exists('query', $parts)) {
            throw new InvalidArgumentException('Query strings are not supported for project URLs.');
        }

        if (array_key_exists('fragment', $parts)) {
            throw new InvalidArgumentException('Fragments are not supported for project URLs.');
        }

        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }

        if ($path !== '/') {
            throw new InvalidArgumentException('Project URL must represent a website root.');
        }

        $port = $parts['port'] ?? null;
        if ($port !== null && (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
            $port = null;
        }

        $authorityHost = str_contains($host, ':') ? "[{$host}]" : $host;

        return $scheme.'://'.$authorityHost.($port ? ':'.$port : '').'/';
    }
}
