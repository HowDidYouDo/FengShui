@php use App\Services\LanguageService; @endphp
    <!DOCTYPE html>
<!-- /resources/views/layouts/app/sidebar.blade.php -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 font-sans">

<!-- Sidebar Container -->
<!-- Wir nutzen bg-white für einen sauberen Look, die Akzente kommen durch die Icons/Buttons -->
<flux:sidebar sticky stashable class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

    <!-- Mobile Toggle -->
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

    <!-- BRAND LOGO -->
    <!-- Ersetzt den Text 'Laravel Starter Kit' durch dein Logo -->
    <a href="{{ route('dashboard') }}" class="px-2 mb-6 flex items-center justify-start" wire:navigate>
        <img src="https://clarity-advisers.com/wp-content/uploads/2019/01/Advisers.png" alt="Clarity Advisers"
             class="h-10 w-auto object-contain">
    </a>

    <!-- NAVIGATION -->
    <flux:navlist variant="outline">
        <!-- Dashboard Link -->
        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                           wire:navigate
                           class="data-[current]:!bg-brand-orange-light data-[current]:!text-brand-orange">
            {{ __('Dashboard') }}
        </flux:navlist.item>


        <!-- Platzhalter für spätere Direktlinks (optional) -->
        <!-- z.B. <flux:navlist.item icon="sparkles" href="#">Flying Stars</flux:navlist.item> -->
    </flux:navlist>
    @if(auth()->user()->hasRole('admin'))
        <flux:navlist variant="outline" class="mt-4 border-t border-zinc-200 dark:border-zinc-700 pt-4">
            <flux:navlist.item icon="shield-check" href="/admin" target="_blank">
                {{ __('Admin Panel') }}
            </flux:navlist.item>
        </flux:navlist>
    @endif

    <!-- Spacer drückt das User-Menü nach unten -->
    <flux:spacer/>

    <!-- DESKTOP USER MENU -->
    <flux:dropdown class="hidden lg:block" position="bottom" align="start">

        <!-- Profil-Anzeige unten links -->
        <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                      icon-trailing="chevrons-up-down"
                      class="hover:bg-zinc-50 dark:hover:bg-zinc-800 rounded-lg p-2 transition-colors"/>
        <!-- Language Switcher -->
        @php
            $availableLanguages = LanguageService::getAvailableLanguages();
            $currentLocale = app()->getLocale();
            $currentLanguageName = LanguageService::getLanguageName($currentLocale);
            $currentFlag = LanguageService::getLanguageFlag($currentLocale);
        @endphp

        <flux:dropdown position="right" align="end">
            <flux:button variant="ghost" class="w-full justify-start px-2 gap-2">
                <span class="text-lg">{{ $currentFlag }}</span>
                <span class="text-sm">{{ $currentLanguageName }}</span>
            </flux:button>

            <flux:menu class="min-w-[180px]">
                @foreach($availableLanguages as $locale => $name)
                    @php
                        $flag = LanguageService::getLanguageFlag($locale);
                        $isActive = $locale === $currentLocale;
                    @endphp
                    <flux:menu.item href="{{ route('language.switch', $locale) }}"
                                    class="{{ $isActive ? 'bg-brand-orange/10 text-brand-orange font-semibold' : '' }}">
                        <div class="flex items-center gap-3 w-full">
                            <span class="text-lg">{{ $flag }}</span>
                            <span class="flex-1">{{ $name }}</span>
                            @if($isActive)
                                <flux:icon.check class="size-4 text-brand-orange"/>
                            @endif
                        </div>
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
        <!-- Das aufklappbare Menü -->
        <flux:menu class="w-[240px]">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                        <!-- Initiale mit Brand-Farben -->
                        <span class="relative flex h-10 w-10 shrink-0 overflow-hidden rounded-lg shadow-sm">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-brand-orange-light text-brand-orange font-bold text-lg">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                        <div class="grid flex-1 text-start leading-tight">
                                <span
                                    class="truncate font-semibold text-zinc-800 dark:text-white">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                </flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full !text-red-600 hover:!bg-red-50">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

<!-- MOBILE HEADER (Sichtbar auf kleinen Screens) -->
<flux:header class="lg:hidden border-b border-zinc-200 bg-white dark:bg-zinc-900 dark:border-zinc-800">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>

    <!-- Logo auch mobil anzeigen -->
    <a href="{{ route('dashboard') }}" class="ml-4" wire:navigate>
        <img src="https://clarity-advisers.com/wp-content/uploads/2019/01/Advisers.png" class="h-8 w-auto">
    </a>

    <flux:spacer/>

    <!-- Mobile User Dropdown -->
    <flux:dropdown position="top" align="end">
        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down"/>

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-brand-orange-light text-brand-orange font-bold">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-zinc-500">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                </flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="w-full !text-red-600">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>

<!-- Hauptinhalt (Dashboard) -->
{{ $slot }}

@fluxScripts
</body>

</html>
