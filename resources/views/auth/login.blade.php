<x-guest-layout>
    <x-slot:title>Sign in - RankWatch</x-slot:title>
    <x-slot:heading>Sign in to RankWatch</x-slot:heading>
    <x-slot:subheading>Monitor your rankings, crawl health, and current SEO issues from one focused dashboard.</x-slot:subheading>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" :value="__('Password')" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-slate-600 hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-950 shadow-sm focus:ring-slate-500" name="remember">
            <span>{{ __('Remember me') }}</span>
        </label>

        <x-primary-button class="w-full">
            {{ __('Sign in') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        New to RankWatch?
        <a href="{{ route('register') }}" class="font-semibold text-slate-950 hover:underline">Create a free account</a>
    </p>
</x-guest-layout>
