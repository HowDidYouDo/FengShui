<!-- resources/views/dashboard.blade.php -->
<x-layouts.app>
    <x-slot name="header">
        <h2 class="font-heading font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Welcome Message -->
            <div class="mb-8 px-4 sm:px-0">
                <h3 class="text-2xl font-bold font-heading text-brand-blue dark:text-brand-blue-light">
                    {{ __('Welcome back') }}, {{ Auth::user()->name }}!
                </h3>
                <p class="text-zinc-600 dark:text-zinc-400 mt-1">
                    {{ __('Find clarity in your life. Choose a module to start.') }}
                </p>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-4 sm:px-0">

                @foreach($features as $feature)
                    @php
                        $license = $myLicenses->get($feature->id);
                        $hasAccess = $license && $license->isValid();

                        if (Auth::user()->hasRole('admin')) {
                            $hasAccess = true;
                        }

                        $expiresText = ($license && $license->expires_at)
                            ? __('Expires: :date', ['date' => $license->expires_at->format('d.m.Y')])
                            : __('Lifetime Access');
                    @endphp

                    <div
                        class="relative group flex flex-col bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">

                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            @if($hasAccess)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                    {{ __('Not Booked') }}
                                </span>
                            @endif
                        </div>

                        <div class="p-6 flex flex-col h-full">
                            <!-- Icon Placeholder -->
                            <div
                                class="w-12 h-12 rounded-lg mb-4 flex items-center justify-center text-lg font-bold
                                    {{ $hasAccess ? 'bg-brand-orange-light text-brand-orange' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800' }}">
                                {{ substr($feature->name, 0, 1) }}
                            </div>

                            <h4 class="text-lg font-bold font-heading text-zinc-900 dark:text-white mb-2">
                                {{ $feature->name }}
                            </h4>

                            <p class="text-sm text-zinc-600 dark:text-zinc-400 flex-grow">
                                {{ $feature->description ?? __('Use this module for your analysis.') }}
                            </p>

                            @if($hasAccess && $license)
                                <div
                                    class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 flex flex-col gap-1">
                                    <span>{{ $expiresText }}</span>
                                    @if($license->quota)
                                        <span>{{ __('Quota: :count remaining', ['count' => $license->quota]) }}</span>
                                    @endif
                                </div>
                            @endif

                            <!-- Action Button Area -->
                            <div class="mt-6">
                                @if($hasAccess)
                                    @php

                                            $routeName = 'modules.' . $feature->code;
                                            $routeExists = Route::has($routeName);
                                            $targetUrl = $routeExists ? route($routeName) : '#';

                                    @endphp

                                    <flux:button variant="primary"
                                        class="w-full !bg-brand-blue hover:!bg-brand-orange !text-white border-0 transition-colors"
                                        :href="$targetUrl" :disabled="!$routeExists">
                                        {{ $routeExists ? __('Open Module') : __('Coming Soon') }}
                                    </flux:button>
                                @else
                                    <flux:button class="w-full cursor-not-allowed opacity-50" disabled>
                                        {{ __('Not Available') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                @if($features->isEmpty())
                    <div class="col-span-full text-center py-12 text-zinc-500">
                        {{ __('No modules found. Please configure features in admin panel.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
