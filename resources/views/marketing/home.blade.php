<x-marketing-layout title="RankWatch - SEO ranking and crawl monitoring" description="Track rankings, catch technical SEO issues, and see the current health of your websites with RankWatch.">
    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_520px] lg:px-8 lg:py-20">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">SEO monitoring for growing websites</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Track rankings. Catch SEO issues. See what changed.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">RankWatch combines keyword monitoring, bounded technical crawls, current SEO health, and simple reports in one Laravel-native workspace.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('register')">Create Free Account</x-button>
                    <x-button :href="route('marketing.pricing')" variant="secondary">View plans</x-button>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm shadow-slate-200/60">
                <div class="rounded-lg border border-slate-200 bg-slate-950 p-4 text-white">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-300">Acme Coffee</p>
                            <p class="mt-1 text-xl font-semibold">SEO health overview</p>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-sm font-semibold text-emerald-200">82 / 100</span>
                    </div>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-slate-300">Keywords</p>
                            <p class="mt-2 text-2xl font-bold">47</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-slate-300">Avg. position</p>
                            <p class="mt-2 text-2xl font-bold">12.4</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3">
                            <p class="text-xs text-slate-300">Open issues</p>
                            <p class="mt-2 text-2xl font-bold">8</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                        <div><p class="font-semibold">best coffee beans jakarta</p><p class="text-slate-500">Current position</p></div>
                        <span class="font-semibold text-emerald-700">4</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                        <div><p class="font-semibold">Missing meta description</p><p class="text-slate-500">Technical SEO issue</p></div>
                        <x-badge tone="medium">Medium</x-badge>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
                        <div><p class="font-semibold">Latest crawl</p><p class="text-slate-500">Queued manual crawl supported</p></div>
                        <span class="text-slate-600">Today</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <h2 class="text-2xl font-bold tracking-tight">Focused monitoring without the enterprise sprawl</h2>
            <p class="mt-3 text-slate-600">RankWatch keeps the core SEO workflow visible: positions, technical issues, current health, and reports.</p>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Rank Tracking</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Track current, previous, and best keyword positions with found and not-found states kept distinct.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">Technical SEO Crawling</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Run bounded crawls for titles, meta descriptions, headings, canonical tags, broken internal links, and more.</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="font-semibold">SEO Health</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">See the latest completed crawl state, open issue severity, SEO score, and project report in one place.</p>
            </article>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold tracking-tight">How it works</h2>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ([['1', 'Add website', 'Create a canonical site-root project for the website you want to monitor.'], ['2', 'Add keywords / run crawl', 'Track search terms and queue a manual technical SEO crawl when needed.'], ['3', 'Monitor changes', 'Review current rankings, open issues, crawl state, and reports from the dashboard.']] as [$step, $title, $copy])
                    <article class="rounded-xl border border-slate-200 bg-white p-5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">{{ $step }}</span>
                        <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $copy }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Start Free. Pro is coming soon.</h2>
                <p class="mt-3 text-slate-600">No checkout exists today. Free is available now; Pro shows the planned growth tier.</p>
            </div>
            <x-button :href="route('marketing.pricing')" variant="secondary">Compare plans</x-button>
        </div>
        <div class="mt-8 grid gap-5 md:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-6">
                <h3 class="text-xl font-semibold">Free</h3>
                <p class="mt-2 text-3xl font-bold">$0</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>1 website</li>
                    <li>10 keywords per site</li>
                    <li>Up to 10 pages per crawl</li>
                    <li>Weekly automatic monitoring</li>
                    <li>Manual crawl, SEO issues, and SEO score</li>
                </ul>
                <x-button :href="route('register')" class="mt-6 w-full">Start Free</x-button>
            </article>
            <article class="rounded-xl border border-slate-900 bg-white p-6">
                <h3 class="text-xl font-semibold">Pro</h3>
                <p class="mt-2 text-3xl font-bold">Coming soon</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>Up to 10 websites</li>
                    <li>100 keywords per site</li>
                    <li>Up to 100 pages per crawl</li>
                    <li>Daily automatic monitoring</li>
                    <li>Keyword drop and crawl completion notifications</li>
                </ul>
                <span class="mt-6 inline-flex w-full items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">No checkout yet</span>
            </article>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-slate-950 py-12 text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Ready to monitor your first website?</h2>
                <p class="mt-2 text-slate-300">Create a Free account and start with one website.</p>
            </div>
            <x-button :href="route('register')" variant="secondary">Create Free Account</x-button>
        </div>
    </section>
</x-marketing-layout>
