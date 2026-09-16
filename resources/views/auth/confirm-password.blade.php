<x-guest-layout>
    <x-slot:title>Confirm password - RankWatch</x-slot:title>
    <x-slot:heading>Confirm your password</x-slot:heading>
    <x-slot:subheading>This protected area needs a fresh password confirmation.</x-slot:subheading>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Confirm password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
