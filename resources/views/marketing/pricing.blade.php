<x-marketing-layout title="RankWatch Pricing" description="Simple pricing for RankWatch SEO monitoring.">
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold">Pricing</h1>
        <p class="mt-4 max-w-2xl text-slate-600">Start small, add projects as your SEO workflow grows.</p>
        <div class="mt-8 grid gap-5 sm:grid-cols-3">
            @foreach (['Starter' => '3 projects', 'Freelancer' => '15 projects', 'Agency' => '50 projects'] as $name => $limit)
                <article class="rounded-lg border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold">{{ $name }}</h2>
                    <p class="mt-2 text-slate-600">{{ $limit }}</p>
                    <x-button :href="route('register')" class="mt-6 w-full">Choose plan</x-button>
                </article>
            @endforeach
        </div>
    </section>
</x-marketing-layout>
