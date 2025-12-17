<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Feature extends Model
{
    use HasTranslations;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price_netto',
        'currency',
        'active',
        'is_default',
        'order',
    ];

    // Definiere welche Felder übersetzbar sind
    public $translatable = ['name', 'description'];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Wer hat dieses Feature gekauft?
    public function userFeatures(): HasMany
    {
        return $this->hasMany(UserFeature::class);
    }
}
