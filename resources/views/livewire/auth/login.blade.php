<x-layouts.auth>
    <!-- /resource/views/livewire/auth/login.blade.php -->
    <div class="flex flex-col gap-6">

        <div class="text-center">
            <h2 class="text-2xl font-bold font-heading text-slate-800 dark:text-white">
                {{ __('Log in to your account') }}
            </h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Enter your email and password below to log in') }}
            </p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0 !text-brand-blue hover:!text-brand-orange transition-colors" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox
                name="remember"
                :label="__('Remember me')"
                :checked="old('remember')"
                class="data-[checked]:!text-brand-orange"
            />

            <div class="flex items-center justify-end">
                <!-- Durch die CSS Änderung ist dieser Button jetzt automatisch Brand-Orange -->
                <flux:button variant="primary" type="submit" class="w-full font-medium shadow-md shadow-orange-200/50 dark:shadow-none" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate class="!text-brand-blue hover:!text-brand-orange transition-colors font-medium">
                    {{ __('Sign up') }}
                </flux:link>
            </div>
        @endif
    </div>
</x-layouts.auth>
