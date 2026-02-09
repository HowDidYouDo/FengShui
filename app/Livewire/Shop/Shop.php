<?php

namespace App\Livewire\Shop;

use App\Models\Feature;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Shop extends Component
{
    public array $cart = [];

    public function mount()
    {
        $cartData = session('cart', []);
        // Ensure the cart is always an array.
        $this->cart = is_array($cartData) ? $cartData : [];
    }

    public function render()
    {
        $user = Auth::user();

        // Ermittele gültige (isValid) User-Features
        $ownedUserFeatures = $user->features()->get()->filter(function ($uf) {
            return $uf->isValid();
        });
        $ownedFeatureIds = $ownedUserFeatures->pluck('feature_id')->toArray();

        // Verberge Features, die bereits gekauft wurden UND kein Kontingent-Feature sind.
        // Features mit Kontingent (quota) können mehrfach gekauft werden.
        $features = Feature::where('active', true)
            ->where(function ($query) use ($ownedFeatureIds) {
                $query->whereNotIn('id', $ownedFeatureIds)
                    ->orWhere('default_quota', '>', 0);
            })
            ->where(function ($query) use ($ownedFeatureIds) {
                $query->whereNull('included_by_id')
                    ->orWhereNotIn('included_by_id', array_merge($ownedFeatureIds, $this->cart));
            })
            ->orderBy('order')
            ->get();

        return view('livewire.shop.shop', [
            'features' => $features,
        ]);
    }

    public function buy(int $featureId): void
    {
        $feature = Feature::find($featureId);
        if (!$feature) return;

        // Wenn dieses Modul bereits durch ein anderes Modul abgedeckt ist (gekauft oder im Warenkorb), dann nicht kaufbar
        $user = Auth::user();
        $ownedUserFeatures = $user->features()->get()->filter(fn($uf) => $uf->isValid());
        $ownedFeatureIds = $ownedUserFeatures->pluck('feature_id')->toArray();

        // Wenn es kein Quota-Feature ist und der User es schon hat, dann nicht kaufbar
        if ($feature->default_quota <= 0 && in_array($featureId, $ownedFeatureIds)) {
            return;
        }

        if ($feature->included_by_id && (in_array($feature->included_by_id, $ownedFeatureIds) || in_array($feature->included_by_id, $this->cart))) {
            return;
        }

        if (!in_array($featureId, $this->cart)) {
            $this->cart[] = $featureId;
            session(['cart' => $this->cart]);
        }
    }
}
