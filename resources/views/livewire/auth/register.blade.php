<x-layouts.auth>
    <!-- /resource/views/livewire/auth/register.blade.php -->
    <div class="flex flex-col gap-6">

        <div class="text-center">
            <h2 class="text-2xl font-bold font-heading text-slate-800 dark:text-white">
                {{ __('Create an account') }}
            </h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Start your journey to clarity today') }}
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input name="name" :label="__('Name')" :value="old('name')" type="text" required autofocus
                autocomplete="name" :placeholder="__('Full name')" />

            <!-- Email Address -->
            <flux:input name="email" :label="__('Email address')" :value="old('email')" type="email" required
                autocomplete="email" :placeholder="__('email@example.com')" />

            <!-- Password -->
            <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password"
                :placeholder="__('Password')" viewable />

            <!-- Confirm Password -->
            <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required
                autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary"
                    class="w-full font-medium shadow-md shadow-orange-200/50 dark:shadow-none"
                    data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate
                class="!text-brand-blue hover:!text-brand-orange transition-colors font-medium">
                {{ __('Log in') }}
            </flux:link>
        </div>
    </div>
</x-layouts.auth>