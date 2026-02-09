<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNinjaConfig extends Model
{
    //
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'encrypted',
    ];

    /**
     * Holt alle relevanten Keys als Array mit leeren Defaults,
     * damit das Filament-Formular immer befüllt ist.
     */
    public static function getFormSettings(): array
    {
        $defaults = [
            'api_url' => '',
            'api_token' => '',
            'is_sandbox' => '0',
        ];

        $settings = self::all()->pluck('value', 'key')->toArray();

        return array_merge($defaults, $settings);
    }

    public static function get(string $key, $default = null)
    {
        return self::where('key', $key)->first()?->value ?? $default;
    }
}
