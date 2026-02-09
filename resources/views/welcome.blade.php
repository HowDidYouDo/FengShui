<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Clarity Advisers') }} - Find Your Life Clarity</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=playfair-display:400,500,600,700|inter:300,400,500,600&display=swap"
        rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="antialiased text-slate-600 bg-white">

    <!-- Navigation -->
    <nav class="absolute top-0 w-full z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-28">
                <!-- Logo Area -->
                <div class="flex-shrink-0 flex items-center">
                    <img src="https://clarity-advisers.com/wp-content/uploads/2019/01/Advisers.png"
                        alt="Clarity Advisers Logo" class="h-16 w-auto logo-shadow">
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                    class="text-slate-500 hover-text-brand-orange px-3 py-2 rounded-md text-sm font-medium transition">{{ __('Dashboard') }}</a>
                            @else
                                <a href="{{ route('login') }}"
                                    class="text-slate-500 hover-text-brand-orange px-3 py-2 rounded-md text-sm font-medium transition">{{ __('Login') }}</a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                        class="bg-brand-orange hover-bg-brand-orange text-white px-6 py-2.5 rounded-full text-sm font-medium transition shadow-lg shadow-orange-200/50">{{ __('Register') }}</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative hero-bg overflow-hidden min-h-[90vh] flex items-center">
        <div
            class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 grid grid-cols-1 md:grid-cols-2 gap-12 items-center">

            <!-- Text Content -->
            <div class="text-center md:text-left z-10">
                <span
                    class="inline-block py-1 px-3 rounded-full bg-brand-blue-light text-brand-blue text-sm font-medium mb-6 border border-blue-100">
                    {{ __('Eike & Xanny') }}
                </span>

                <h1 class="text-5xl lg:text-6xl font-bold text-slate-800 mb-6 serif leading-tight">
                    {{ __('Find Your') }} <br>
                    <span class="text-brand-orange">{{ __('Life Clarity') }}</span>
                </h1>

                <p class="mt-4 text-lg text-slate-600 font-light leading-relaxed max-w-lg mx-auto md:mx-0">
                    {{ __('Combine ancient shamanic wisdom with modern structure.') }}
                    {{ __('Use our digital tools for') }} <strong>{{ __('Flying Stars') }}</strong>,
                    <strong>{{ __('Bagua') }}</strong>, {{ __('and your') }}
                    <strong>{{ __('Client Management') }}</strong>.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row justify-center md:justify-start gap-4">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="group px-8 py-4 bg-brand-orange text-white text-lg font-medium rounded-full shadow-xl shadow-orange-200 hover-bg-brand-orange transition-all duration-300 transform hover:-translate-y-1">
                            {{ __('Get Started') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="group px-8 py-4 bg-brand-orange text-white text-lg font-medium rounded-full shadow-xl shadow-orange-200 hover-bg-brand-orange transition-all duration-300 transform hover:-translate-y-1">
                            {{ __('Login') }}
                        </a>
                    @endif

                    <a href="#features"
                        class="px-8 py-4 border border-slate-200 text-slate-600 text-lg font-medium rounded-full hover:bg-white hover:shadow-md transition-all bg-white/50 backdrop-blur-sm">
                        {{ __('Explore Features') }}
                    </a>
                </div>
            </div>

            <!-- Hero Visual -->
            <div class="relative hidden md:block">
                <div class="relative w-full aspect-square flex items-center justify-center">
                    <div class="absolute w-4/5 h-4/5 bg-gradient-to-tr from-brand-blue-light to-white rounded-full shadow-2xl shadow-blue-100/50 animate-pulse"
                        style="animation-duration: 4s;"></div>
                    <div
                        class="absolute w-2/3 h-2/3 bg-gradient-to-br from-brand-orange-light to-white rounded-full shadow-inner opacity-80">
                    </div>

                    <div class="absolute top-[20%] right-[20%] p-4 bg-white rounded-2xl shadow-lg animate-bounce text-brand-orange"
                        style="animation-duration: 3s;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <div class="absolute bottom-[30%] left-[20%] p-4 bg-white rounded-2xl shadow-lg animate-bounce text-brand-blue"
                        style="animation-duration: 4s;">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Grid -->
    <div id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-brand-blue font-semibold tracking-wide uppercase text-sm">{{ __('Professional Tools') }}
                </h2>
                <p class="mt-2 text-3xl md:text-4xl font-bold text-slate-800 serif">
                    {{ __('Shamanism meets Technology') }}
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1: Flying Stars -->
                <div
                    class="group p-8 bg-white rounded-3xl border border-slate-100 hover:border-brand-orange-light hover:shadow-xl hover:shadow-orange-100/40 transition-all duration-300">
                    <div
                        class="w-14 h-14 bg-brand-orange-light rounded-2xl flex items-center justify-center mb-6 text-brand-orange">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3 serif">{{ __('Flying Stars') }}</h3>
                    <p class="text-slate-500 leading-relaxed">
                        {{ __('Calculate energetic influences for every room. Recognize the invisible and use time quality for your Feng Shui.') }}
                    </p>
                </div>

                <!-- Card 2: Bagua -->
                <div
                    class="group p-8 bg-white rounded-3xl border border-slate-100 hover:border-brand-blue-light hover:shadow-xl hover:shadow-blue-100/40 transition-all duration-300">
                    <div
                        class="w-14 h-14 bg-brand-blue-light rounded-2xl flex items-center justify-center mb-6 text-brand-blue">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3 serif">{{ __('Digital Bagua') }}</h3>
                    <p class="text-slate-500 leading-relaxed">
                        {{ __('Overlay the Bagua on floor plans. Find life areas and activate them specifically for better flow and harmony.') }}
                    </p>
                </div>

                <!-- Card 3: Clients -->
                <div
                    class="group p-8 bg-white rounded-3xl border border-slate-100 hover:border-brand-orange-light hover:shadow-xl hover:shadow-orange-100/40 transition-all duration-300">
                    <div
                        class="w-14 h-14 bg-brand-orange-light rounded-2xl flex items-center justify-center mb-6 text-brand-orange">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3 serif">{{ __('Client Management') }}</h3>
                    <p class="text-slate-500 leading-relaxed">
                        {{ __('Manage consultations securely. Create professional reports and accompany your clients on their journey.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quote Section -->
    <div class="bg-brand-blue-light/30 py-20 border-t border-slate-100">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <p class="text-2xl md:text-3xl text-slate-700 font-light italic serif leading-relaxed">
                "{{ __('Once you have answered your life questions, you will see your world much more clearly.') }}"
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <div class="w-8 h-1 bg-brand-orange rounded-full"></div>
                <p class="text-slate-500 font-medium">{{ __('Eike & Xanny') }}</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white text-slate-400 py-12 border-t border-slate-100">
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-center md:text-left">
                <img src="https://clarity-advisers.com/wp-content/uploads/2019/01/Advisers.png" alt="Clarity Advisers"
                    class="h-8 w-auto opacity-50 grayscale hover:grayscale-0 transition-all">
                <p class="text-sm mt-2 text-slate-400">© {{ date('Y') }} Clarity Advisers.</p>
            </div>
            <div class="flex gap-6 text-sm">
                <a href="https://clarity-advisers.com/impressum"
                    class="hover-text-brand-blue transition">{{ __('Impressum') }}</a>
                <a href="https://clarity-advisers.com/datenschutz"
                    class="hover-text-brand-blue transition">{{ __('Datenschutz') }}</a>
            </div>
        </div>
    </footer>
</body>

</html>
