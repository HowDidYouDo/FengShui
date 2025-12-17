<?php
// /resources/views/livewire/settings/appearance.blade.php

use App\Services\LanguageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {
    public string $locale;

    public function mount(): void
    {
        // Lade aktuelle Sprache vom User (DB) oder Fallback auf App-Locale
        $this->locale = Auth::user()->locale ?? App::getLocale();
    }

    // Wird aufgerufen, wenn sich der Wert ändert
    public function switchLocale($value): Redirector|RedirectResponse
    {
        // 1. In DB speichern
        Auth::user()->update(['locale' => $value]);

        // 2. In Session speichern (für Middleware)
        Session::put('locale', $value);

        // 3. Seite neu laden, damit Änderungen sichtbar werden
        return redirect(request()->header('Referer'));
    }
}; ?>

<section class="w-full space-y-6">
    @include('partials.settings-heading')

    <!-- Appearance (Dark Mode) -->
    <x-settings.layout :heading="__('Appearance & Language')" :subheading="__('Customize looks and localization.')">
        <div class="space-y-8">
            <!-- SECTION 1: Dark Mode -->
            <div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-4">{{ __('Appearance') }}</h3>
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            <flux:separator/>
            <!-- Language Settings -->

            <div>
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-4">{{ __('Language') }}</h3>

                @php
                    $availableLanguages = LanguageService::getAvailableLanguages();
                @endphp

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                    @foreach($availableLanguages as $localeCode => $languageName)
                        @php
                            $flag = LanguageService::getLanguageFlag($localeCode);
                            $isActive = $locale === $localeCode;
                        @endphp

                        <button type="button" wire:click="switchLocale('{{ $localeCode }}')" class="group relative flex items-center gap-3 p-4 rounded-xl border text-left transition-all
                                                {{ $isActive
                        ? 'bg-brand-orange/10 border-brand-orange ring-2 ring-brand-orange shadow-sm'
                        : 'bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-white/5 hover:border-brand-blue/50'
                                                }}">
                            <!-- Flag Emoji -->
                            <div class="text-3xl shrink-0">
                                {{ $flag }}
                            </div>

                            <!-- Language Name -->
                            <div class="flex-1 min-w-0">
                                <div
                                    class="font-semibold text-zinc-900 dark:text-white {{ $isActive ? 'text-brand-orange' : '' }} truncate">
                                    {{ $languageName }}
                                </div>
                                <div class="text-xs text-zinc-500 uppercase">
                                    {{ $localeCode }}
                                </div>
                            </div>

                            <!-- Check Icon -->
                            @if($isActive)
                                <flux:icon.check-circle class="size-5 text-brand-orange shrink-0" variant="solid"/>
                            @else
                                <div
                                    class="size-5 rounded-full border-2 border-zinc-300 dark:border-zinc-700 shrink-0 group-hover:border-brand-blue/50">
                                </div>
                            @endif
                        </button>
                    @endforeach
                </div>

                <p class="mt-4 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                    <flux:icon.arrow-path class="size-3"/>
                    {{ __('Page will reload automatically after selection.') }}
                </p>
            </div>
        </div>
    </x-settings.layout>

</section>
