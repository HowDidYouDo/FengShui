<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\InvoiceNinjaConfig;
use Exception;
use Illuminate\Support\Str;
use InvoiceNinja\Sdk\InvoiceNinja;

class InvoiceNinjaService
{
    public function testConnection(?array $config = null): array
    {
        try {
            $ninja = $this->sdk($config);

            // Wir nutzen Clients statt Products zum Testen,
            // das ist der stabilste Standard-Endpunkt für einen Check.
            $ninja->clients->all(['per_page' => 1]);

            return ['success' => true, 'message' => __('Connection successful! The SDK is communicating correctly with Ninja.')];
        } catch (Exception $e) {
            return ['success' => false, 'message' => __('Connection error: :error', ['error' => $e->getMessage()])];
        }
    }

    protected function sdk(?array $configOverride = null): InvoiceNinja
    {
        $config = $configOverride ? collect($configOverride) : InvoiceNinjaConfig::all()->pluck('value', 'key');

        $token = $config->get('api_token');
        $url = rtrim($config->get('api_url'), '/');
        $secret = $config->get('api_secret_token');

        $ninja = new InvoiceNinja($token);

        // Das SDK erwartet bei selbstgehosteten Instanzen den Pfad inkl. /api/v1
        $ninja->setUrl(rtrim($url, '/'));

//        if ($secret) {
//            $ninja->setSecret($secret);
//        }

        return $ninja;
    }

    public function syncFeaturesFromNinja(): array
    {
        $ninja = $this->sdk();
        $products = $ninja->products->all();

        $results = ['created' => 0, 'updated' => 0];

        foreach ($products as $product) {
            // Filter nach deinem Custom-Field
            $type = $product['custom_value1'] ?? null;

            if (!in_array($type, ['FS_R', 'FS_O'])) {
                continue;
            }

            $purchaseType = ($type === 'FS_R') ? 'subscription' : 'lifetime';
            $priceInCent = (int)round($product['cost'] * 100);

            // Suche nach bestehendem Feature über die Integer-ID
            $feature = Feature::where('ninja_id', $product['id'])->first();

            if ($feature) {
                // Update: Nur Preise und technische Typen
                $feature->update(['price_netto' => $priceInCent, 'purchase_type' => $purchaseType, 'active' => true,]);
                $results['updated']++;
            } else {
                // Create: Neu anlegen mit initialen Übersetzungen für alle Locales
                $locales = ['de', 'en', 'es', 'fr'];
                $names = [];
                $descriptions = [];
                foreach ($locales as $locale) {
                    $names[$locale] = $product['product_key'];
                    $descriptions[$locale] = $product['notes'] ?? '';
                }

                Feature::create([
                    'ninja_id' => $product['id'],
                    'code' => Str::slug($product['product_key']),
                    'name' => $names,
                    'description' => $descriptions,
                    'price_netto' => $priceInCent,
                    'purchase_type' => $purchaseType,
                    'currency' => 'EUR',
                    'active' => true,
                ]);
                $results['created']++;
            }
        }

        return $results;
    }

}
