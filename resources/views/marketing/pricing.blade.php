<x-marketing-layout title="RankWatch Pricing" description="Simple pricing for RankWatch SEO monitoring.">
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold">Pricing</h1>
        <p class="mt-4 max-w-2xl text-slate-600">Start free. Pro is the planned growth tier; billing is not implemented yet.</p>
        <div class="mt-8 grid gap-5 md:grid-cols-2">
            <article class="rounded-lg border border-slate-200 p-6">
                <h2 class="text-xl font-semibold">Free</h2>
                <p class="mt-2 text-3xl font-bold">$0</p>
                <p class="mt-2 text-slate-600">For personal websites and trying RankWatch.</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>1 website</li>
                    <li>10 keywords</li>
                    <li>Up to 10 pages per crawl</li>
                    <li>Weekly automatic monitoring</li>
                    <li>Manual crawl, SEO score, technical issues, ranking history, basic report</li>
                    <li>Critical SEO issue notifications</li>
                </ul>
                <x-button :href="route('register')" class="mt-6 w-full">Get started</x-button>
            </article>
            <article class="rounded-lg border border-slate-900 p-6">
                <h2 class="text-xl font-semibold">Pro</h2>
                <p class="mt-2 text-3xl font-bold">Coming soon</p>
                <p class="mt-2 text-slate-600">For larger monitoring workflows.</p>
                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li>Up to 10 websites</li>
                    <li>100 keywords per website</li>
                    <li>Up to 100 pages per crawl</li>
                    <li>Daily monitoring</li>
                    <li>Keyword drop and crawl completion notifications</li>
                    <li>Planned later: historical comparisons and PDF export</li>
                </ul>
                <span class="mt-6 inline-flex w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800">View Pro features</span>
            </article>
        </div>
    </section>
</x-marketing-layout>
