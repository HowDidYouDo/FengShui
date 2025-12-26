<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Zeigt das User-Dashboard mit allen verfügbaren Modulen.
     */
    public function index(): View
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        // 1. Alle Features laden
        $allFeatures = Feature::where('active', true)
            ->orderBy('order', 'asc')
            ->get();

        // 2. User-Lizenzen laden
        $myLicenses = $user->features()
            ->where('active', true)
          //  ->orderBy('features.order', 'asc')
            ->get()
            ->keyBy('feature_id');

        // 3. Features sortieren: Aktive zuerst
        $sortedFeatures = $allFeatures->sortByDesc(function ($feature) use ($myLicenses, $isAdmin) {
            // Prüfen, ob Zugriff besteht
            $license = $myLicenses->get($feature->id);
            $hasAccess = ($license && $license->isValid()) || $isAdmin;

            // Sortier-Logik:
            // 1 = Zugriff (kommt nach oben)
            // 0 = Kein Zugriff (kommt nach unten)
            return $hasAccess ? 1 : 0;
        });

        return view('dashboard', [
            'features' => $sortedFeatures,
            'myLicenses' => $myLicenses,
        ]);
    }
}
