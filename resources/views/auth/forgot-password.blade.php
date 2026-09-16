<x-guest-layout>
    <x-slot:title>Reset password - RankWatch</x-slot:title>
    <x-slot:heading>Reset your password</x-slot:heading>
    <x-slot:subheading>Enter your email and we will send a reset link if the account exists.</x-slot:subheading>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Send reset link') }}
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Remembered it?
        <a href="{{ route('login') }}" class="font-semibold text-slate-950 hover:underline">Sign in</a>
    </p>
</x-guest-layout>
