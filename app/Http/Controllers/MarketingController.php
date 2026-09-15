<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class MarketingController extends Controller
{
    public function home()
    {
        return view('marketing.home');
    }

    public function pricing()
    {
        return view('marketing.pricing');
    }

    public function sitemap(): Response
    {
        return response()
            ->view('marketing.sitemap', ['urls' => [route('marketing.home'), route('marketing.pricing')]])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /dashboard\nDisallow: /projects\nDisallow: /profile\n\nSitemap: ".route('sitemap')."\n";

        return response($body)->header('Content-Type', 'text/plain');
    }
}
