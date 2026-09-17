<x-app-layout>
    <x-slot:title>Settings - RankWatch</x-slot:title>
    <x-slot:heading>Settings</x-slot:heading>
    <x-slot:subheading>Manage account access, notifications, and plan context.</x-slot:subheading>

    @php
        $projectCount = $user->projects()->count();
        $projectLimit = $user->planLimit('projects');
        $keywordLimit = $user->planLimit('keywords_per_project');
        $crawlLimit = $user->planLimit('crawl_pages');
        $crawlFrequency = ucfirst((string) $user->planLimit('crawl_frequency'));
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="min-w-0 space-y-6">
            <x-card>
                @include('profile.partials.update-profile-information-form')
            </x-card>

            <x-card>
                @include('profile.partials.update-password-form')
            </x-card>

            <x-card class="border-red-200">
                @include('profile.partials.delete-user-form')
            </x-card>
        </div>

        <aside class="min-w-0 space-y-6">
            <x-card>
                <h2 class="text-lg font-semibold">Current plan</h2>
                <p class="mt-1 text-sm text-slate-500">Factual workspace limits for this account.</p>
                <div class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                    <div>
                        <p class="font-semibold text-slate-950">{{ ucfirst($user->plan) }}</p>
                        @if ($user->isPro())
                            <p class="text-sm text-slate-500">Pro - Coming soon</p>
                        @else
                            <p class="text-sm text-slate-500">Free workspace</p>
                        @endif
                    </div>
                </div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Websites</dt><dd class="font-semibold">{{ $projectCount }} / {{ $projectLimit }}</dd></div>
                        <div class="mt-2 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-slate-950" style="width: {{ min(100, $projectLimit ? ($projectCount / $projectLimit) * 100 : 0) }}%"></div></div>
                    </div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Keywords</dt><dd class="font-semibold">{{ $keywordLimit }} per website</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Crawl limit</dt><dd class="font-semibold">Up to {{ $crawlLimit }} pages</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Monitoring</dt><dd class="font-semibold">{{ $crawlFrequency }}</dd></div>
                </dl>
            </x-card>
        </aside>
    </div>
</x-app-layout>
