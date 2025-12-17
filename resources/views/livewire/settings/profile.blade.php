<?php
// /resources/views/livewire/settings
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $birth_place = '';
    public string $birth_date = '';
    public string $birth_time = '';
    public string $gender = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->birth_place = $user->birth_place ?? '';
        $this->birth_date = $user->birth_date ? $user->birth_date->format('Y-m-d') : '';
        $this->birth_time = $user->birth_time ? Carbon::parse($user->birth_time)->format('H:i') : '';
        $this->gender = $user->gender ?? 'm';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'gender' => ['required', 'in:m,f'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);

    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <!-- New Personal Data Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Left Column: Birth Place & Gender -->
                <div class="space-y-6">
                    <flux:input wire:model="birth_place" :label="__('Birth Place')" icon="map-pin" type="text"
                        :placeholder="__('e.g. Hamburg')" />

                    <flux:radio.group wire:model="gender" :label="__('Biological Sex')">
                        <div class="flex gap-4">

                            <!-- Male Option -->
                            <label class="flex-1 relative cursor-pointer">
                                <input type="radio" wire:model="gender" value="m" class="peer sr-only">
                                <div
                                    class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                    <!-- Icon Male -->
                                    <flux:icon.user
                                        class="size-5 text-zinc-400 peer-checked:text-brand-orange group-hover:text-brand-blue" />
                                    <span
                                        class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                        {{ __('Male') }}
                                    </span>
                                </div>
                                <!-- Kleiner Check-Haken oben rechts (optional) -->
                                <div
                                    class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                    <flux:icon.check-circle class="size-4" />
                                </div>
                            </label>

                            <!-- Female Option -->
                            <label class="flex-1 relative cursor-pointer">
                                <input type="radio" wire:model="gender" value="f" class="peer sr-only">
                                <div
                                    class="p-4 rounded-xl border-2 border-zinc-200 dark:border-zinc-700 hover:border-brand-blue dark:hover:border-brand-blue peer-checked:border-brand-orange peer-checked:bg-brand-orange/5 transition-all flex items-center justify-center gap-3 h-full">
                                    <!-- Icon Female -->
                                    <flux:icon.user-circle
                                        class="size-5 text-zinc-400 peer-checked:text-brand-orange group-hover:text-brand-blue" />
                                    <span
                                        class="font-medium text-zinc-700 dark:text-zinc-300 peer-checked:text-brand-orange">
                                        {{ __('Female') }}
                                    </span>
                                </div>
                                <!-- Kleiner Check-Haken oben rechts (optional) -->
                                <div
                                    class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 text-brand-orange transition-opacity">
                                    <flux:icon.check-circle class="size-4" />
                                </div>
                            </label>

                        </div>
                    </flux:radio.group>

                    <flux:text size="xs" class="text-zinc-500 mt-1">
                        {{ __('Required for calculation of energetic cycles.') }}
                    </flux:text>
                </div>

                <!-- Right Column: Date & Time -->
                <div class="grid grid-cols-2 gap-4 content-start">
                    <flux:input wire:model="birth_date" :label="__('Birth Date')" type="date" icon="calendar" />

                    <flux:input wire:model="birth_time" :label="__('Birth Time')" type="time" icon="clock" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
