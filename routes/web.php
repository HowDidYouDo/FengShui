<?php
// /routes/web.php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SecureMediaController;
use App\Livewire\Modules\Crm\Index;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('language/{locale}', function ($locale) {
    // Nur erlaubte Sprachen zulassen (automatisch aus verfügbaren JSON-Dateien)
    if (!\App\Services\LanguageService::isValidLocale($locale)) {
        abort(400);
    }

    // 1. In Session speichern
    Session::put('locale', $locale);

    // 2. Wenn User eingeloggt -> In Datenbank speichern
    if (auth()->check()) {
        auth()->user()->update(['locale' => $locale]);
    }

    return back(); // Zurück zur vorherigen Seite
})->name('language.switch');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::get('media/floor-plans/{floorPlan}/{media}', [SecureMediaController::class, 'show'])
        ->name('media.floor-plans');

    Route::prefix('modules')->name('modules.')->group(function () {
        // Die Index-Seite (Weiche)
        Volt::route('bagua', 'modules.bagua.index')->name('bagua');
        Volt::route('editor/{floorPlan}', 'modules.bagua.editor')->name('bagua.editor');
        // Die Detail-Seite (mit ID)
        Volt::route('bagua/{customer}', 'modules.bagua.show')->name('bagua.show');
        Route::get('/crm', Index::class)->name('crm');
    });

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
