<x-marketing-layout title="RankWatch - Simple SEO monitoring" description="Simple SEO monitoring for websites that want to grow. Track keywords, crawl SEO issues, and print client-ready reports.">
    <section class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.1fr_.9fr] lg:px-8">
        <div>
            <p class="font-semibold text-emerald-700">Simple SEO monitoring for websites that want to grow.</p>
            <h1 class="mt-4 text-4xl font-bold tracking-tight sm:text-5xl">RankWatch</h1>
            <p class="mt-5 max-w-2xl text-lg text-slate-600">Track keyword movement, crawl website issues, and share clear SEO reports without an enterprise tool getting in the way.</p>
            <div class="mt-8 flex gap-3">
                <x-button :href="route('register')">Start monitoring</x-button>
                <x-button :href="route('login')" variant="secondary">Login</x-button>
            </div>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-5">
            <div class="grid grid-cols-2 gap-3">
                <x-card><p class="text-sm text-slate-500">SEO Score</p><p class="mt-2 text-3xl font-bold">82</p></x-card>
                <x-card><p class="text-sm text-slate-500">Keywords</p><p class="mt-2 text-3xl font-bold">47</p></x-card>
                <x-card><p class="text-sm text-slate-500">Avg. Position</p><p class="mt-2 text-3xl font-bold">12.4</p></x-card>
                <x-card><p class="text-sm text-slate-500">Issues</p><p class="mt-2 text-3xl font-bold">8</p></x-card>
            </div>
        </div>
    </section>
    <section class="border-t border-slate-200 bg-slate-50 py-14">
        <div class="mx-auto grid max-w-6xl gap-5 px-4 sm:grid-cols-3 sm:px-6 lg:px-8">
            @foreach (['Keyword tracking', 'Lightweight crawling', 'Client-ready reports'] as $feature)
                <article class="rounded-lg border border-slate-200 bg-white p-5">
                    <h2 class="text-lg font-semibold">{{ $feature }}</h2>
                    <p class="mt-2 text-sm text-slate-600">Focused SEO data for owners, freelancers, and small agencies.</p>
                </article>
            @endforeach
        </div>
    </section>
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">Pricing</h2>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <article class="rounded-lg border border-slate-200 p-5">
                <h3 class="font-semibold">Free</h3>
                <p class="mt-3 text-3xl font-bold">$0</p>
                <p class="mt-2 text-sm text-slate-600">1 website, 10 keywords, up to 10 pages per crawl, weekly automatic monitoring.</p>
            </article>
            <article class="rounded-lg border border-slate-900 p-5">
                <h3 class="font-semibold">Pro</h3>
                <p class="mt-3 text-3xl font-bold">Coming soon</p>
                <p class="mt-2 text-sm text-slate-600">Up to 10 websites, 100 keywords per website, up to 100 pages per crawl, daily monitoring.</p>
            </article>
        </div>
    </section>
    <section class="mx-auto max-w-3xl px-4 pb-16 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold">FAQ</h2>
        <div class="mt-5 space-y-4">
            <article><h3 class="font-semibold">Does RankWatch scrape Google?</h3><p class="mt-1 text-slate-600">No. The ranking checker is built for a configurable SERP provider.</p></article>
            <article><h3 class="font-semibold">Is it built for agencies?</h3><p class="mt-1 text-slate-600">Yes, for small agencies that need simple project monitoring and reports.</p></article>
        </div>
    </section>
</x-marketing-layout>
