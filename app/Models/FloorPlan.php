<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FloorPlan extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'project_id',
        'title',
        'sort_order',
        'outer_bounds',
        'room_data',
    ];

    protected $casts = [
        'outer_bounds' => 'array', // Automatische JSON Konvertierung
        'room_data' => 'array',    // Automatische JSON Konvertierung
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function baguaNotes(): HasMany
    {
        return $this->hasMany(BaguaNote::class);
    }

    // Helper: Liefert das Bild
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('floor_plans')
            ->useDisk('floorplans')
            ->singleFile(); // Nur ein Bild pro Plan
    }
}
