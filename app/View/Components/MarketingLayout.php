<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MarketingLayout extends Component
{
    public function __construct(
        public string $title = 'RankWatch - Simple SEO monitoring',
        public string $description = 'Simple SEO monitoring for websites that want to grow.',
    ) {
    }

    public function render(): View
    {
        return view('layouts.marketing');
    }
}
