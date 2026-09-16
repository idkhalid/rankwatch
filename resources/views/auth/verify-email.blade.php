<x-guest-layout>
    <x-slot:title>Verify email - RankWatch</x-slot:title>
    <x-slot:heading>Verify your email</x-slot:heading>
    <x-slot:subheading>Before opening your monitoring workspace, confirm your email address from the link we sent.</x-slot:subheading>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend verification email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-semibold text-slate-600 hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-500">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
