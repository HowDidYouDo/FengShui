<?php

namespace App\Livewire\Shop;

use App\Models\Feature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Checkout extends Component
{
    public Collection $items;
    public int $total = 0;

    public function mount()
    {
        $this->loadCart();
    }

    protected function loadCart(): void
    {
        $cartIds = session('cart', []);

        // Defensively ensure cartIds is always an array.
        if (!is_array($cartIds)) {
            $cartIds = [];
        }

        if (empty($cartIds)) {
            $this->items = collect();
            $this->total = 0;
            // Also, clean up any invalid session data.
            if (session()->has('cart')) {
                session()->forget('cart');
            }
            return;
        }

        $user = Auth::user();
        $ownedUserFeatures = $user->features()->get()->filter(fn($uf) => $uf->isValid());
        $ownedFeatureIds = $ownedUserFeatures->pluck('feature_id')->toArray();

        // Lade Features basierend auf Cart-IDs
        $items = Feature::whereIn('id', $cartIds)->get();

        // Filtere Features, die durch andere (gekaufte oder im Warenkorb befindliche) Features inkludiert sind.
        // Auch hier: Quota-Features dürfen mehrfach vorkommen, solange sie nicht inkludiert sind.
        $filtered = $items->reject(function ($feature) use ($ownedFeatureIds, $cartIds) {
            // Wenn es inkludiert wird durch ein anderes Modul, das man hat oder kauft, dann ablehnen.
            if ($feature->included_by_id && (in_array($feature->included_by_id, $ownedFeatureIds) || in_array($feature->included_by_id, $cartIds))) {
                return true;
            }

            // Wenn es kein Quota-Feature ist und man es schon hat, dann ablehnen.
            if ($feature->default_quota <= 0 && in_array($feature->id, $ownedFeatureIds)) {
                return true;
            }

            return false;
        });

        // Synchronisiere den Warenkorb mit der gefilterten Liste
        $filteredIds = $filtered->pluck('id')->toArray();
        if ($filteredIds !== $cartIds) {
            session(['cart' => $filteredIds]);
        }

        $this->items = $filtered;
        $this->total = $this->items->sum('price_netto');
    }

    public function render()
    {
        return view('livewire.shop.checkout');
    }

    public function remove(int $featureId): void
    {
        $cart = session('cart', []);
        // Ensure cart is an array before using array_diff
        $cart = is_array($cart) ? $cart : [];
        $cart = array_diff($cart, [$featureId]);
        session(['cart' => $cart]);

        $this->loadCart();
    }

    public function processPurchase(): void
    {
        if ($this->items->isEmpty()) {
            return;
        }

        $user = Auth::user();

        foreach ($this->items as $feature) {
            // Wir suchen, ob der User dieses Feature bereits hat
            $userFeature = \App\Models\UserFeature::where('user_id', $user->id)
                ->where('feature_id', $feature->id)
                ->first();

            if ($userFeature && $feature->default_quota > 0) {
                // Wenn es ein Quota-Feature ist, addieren wir das neue Kontingent einfach dazu
                $userFeature->quota += $feature->default_quota;

                // Falls es eine Subscription ist, verlängern wir sie auch (vereinfachte Logik)
                if ($userFeature->expires_at) {
                    if ($feature->renewal_period === 'monthly') {
                        $userFeature->expires_at = $userFeature->expires_at->addMonth();
                    } else {
                        $userFeature->expires_at = $userFeature->expires_at->addYear();
                    }
                }
                $userFeature->save();
            } else {
                // Ansonsten neu anlegen oder (falls ohne Quota) bestehendes ignorieren/überschreiben
                \App\Models\UserFeature::updateOrCreate(
                    ['user_id' => $user->id, 'feature_id' => $feature->id],
                    [
                        'quota' => $feature->default_quota,
                        'active' => true,
                        'expires_at' => ($feature->purchase_type === 'subscription')
                            ? ($feature->renewal_period === 'monthly' ? now()->addMonth() : now()->addYear())
                            : null,
                    ]
                );
            }
        }

        // Warenkorb leeren
        session()->forget('cart');

        $this->dispatch('notify', message: __('Purchase successful! Your licenses have been updated.'));

        $this->redirectRoute('dashboard');
    }
}
