<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<!-- /resources/views/partials/head.blade.php -->
<title>{{ $title ?? config('app.name', 'Clarity Advisers') }}</title>

<!-- Favicons (Pfade ggf. anpassen, falls Dateien noch fehlen) -->
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<!-- Fonts: Wir nutzen Google Fonts (Roboto & Merriweather Sans) statt fonts.bunny -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Merriweather+Sans:ital,wght@0,300..800;1,300..800&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
    rel="stylesheet">

<!-- Styles & Scripts -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

<!-- Flux UI Appearance Script (für Darkmode etc.) -->
@fluxAppearance
