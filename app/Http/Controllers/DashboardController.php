<?php

namespace App\Http\Controllers;

use App\Models\Feature;
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
            ->get()
            ->keyBy('feature_id');

        // Ermittele IDs der Features, auf die der User bereits gültigen Zugriff hat
        if ($isAdmin) {
            $ownedFeatureIds = $allFeatures->pluck('id')->toArray();
        } else {
            $ownedFeatureIds = $myLicenses->filter->isValid()->pluck('feature_id')->toArray();
        }

        // 3. Features ausblenden, die durch andere gekaufte Features bereits inkludiert sind
        $allFeatures = $allFeatures->reject(function ($feature) use ($ownedFeatureIds) {
            return $feature->included_by_id && in_array($feature->included_by_id, $ownedFeatureIds);
        });

        // 4. Prüfen, ob der User Zugriff auf ALLE aktiven Module hat (nach Filterung)
        $hasAccessToAll = $allFeatures->every(function ($feature) use ($myLicenses, $isAdmin) {
            $license = $myLicenses->get($feature->id);
            return $isAdmin || ($license && $license->isValid());
        });

        // 5. Features sortieren gemäß Anforderung
        if ($hasAccessToAll) {
            // Wenn alle gekauft: Verfügbare (Route existiert) zuerst, dann nach Order
            $sortedFeatures = $allFeatures->sortBy(function ($feature) {
                $routeExists = \Illuminate\Support\Facades\Route::has('modules.' . $feature->code);
                return [
                    $routeExists ? 0 : 1, // 0 kommt vor 1 in sortBy (Verfügbare zuerst)
                    $feature->order,
                ];
            });
        } else {
            // Ansonsten: Strikte Sortierung nach Ordnungsnummer
            $sortedFeatures = $allFeatures->sortBy('order');
        }

        return view('dashboard', [
            'features' => $sortedFeatures,
            'myLicenses' => $myLicenses,
        ]);
    }
}
