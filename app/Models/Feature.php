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
        'purchase_type',
        'renewal_period',
        'default_quota',
        'ninja_id',
        'included_by_id',
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

    public function includedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Feature::class, 'included_by_id');
    }

    public function includes(): HasMany
    {
        return $this->hasMany(Feature::class, 'included_by_id');
    }
}
