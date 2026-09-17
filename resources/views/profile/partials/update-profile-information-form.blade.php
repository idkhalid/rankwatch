<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-950">Profile information</h2>
        <p class="mt-1 text-sm text-slate-500">Update your account name, email address, and global email notification preference.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    {{ __('Your email address is unverified.') }}
                    <button form="send-verification" class="font-semibold underline hover:text-amber-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                        {{ __('Re-send verification email.') }}
                    </button>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-emerald-700">{{ __('A new verification link has been sent to your email address.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <label for="email_notifications" class="flex items-start gap-3 rounded-lg border border-slate-200 p-4">
            <input id="email_notifications" type="checkbox" name="email_notifications" value="1" class="mt-1 rounded border-slate-300 text-slate-950 shadow-sm focus:ring-slate-500" @checked(old('email_notifications', $user->email_notifications))>
            <span>
                <span class="block text-sm font-medium text-slate-900">Email notifications</span>
                <span class="mt-1 block text-sm text-slate-500">Receive enabled crawl, critical issue, and ranking drop emails when your plan allows them.</span>
            </span>
        </label>

        <div class="flex flex-wrap items-center gap-4">
            <x-button type="submit">Save Profile</x-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm font-medium text-emerald-700">Saved.</p>
            @endif
        </div>
    </form>
</section>
