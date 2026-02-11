<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'settled_year',
        'compass_direction',
        'sitting_direction',
        'ventilation_direction',
        'period',
        'use_facing_direction',
        'facing_mountain',
        'is_replacement_chart',
        'special_chart_type',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($project) {
            $project->floorPlans()->each(function ($floorPlan) {
                $floorPlan->delete();
            });
        });
    }

    protected $casts = [
        'settled_year' => 'integer',
        'compass_direction' => 'decimal:4',
        'sitting_direction' => 'decimal:4',
        'ventilation_direction' => 'decimal:4',
        'period' => 'integer',
        'use_facing_direction' => 'boolean',
        'is_replacement_chart' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class);
    }

    /**
     * Gibt den Direction-Typ als String zurück
     */
    public function getDirectionType(): string
    {
        return $this->use_facing_direction ? 'Facing' : 'Ventilation';
    }

    /**
     * Prüft ob Direction gesetzt ist
     */
    public function hasValidDirection(): bool
    {
        $direction = $this->getActiveDirection();

        return $direction !== null && $direction >= 0 && $direction <= 360;
    }

    /**
     * Gibt die aktive Blickrichtung (Facing Direction) für die Flying Stars Berechnung zurück.
     * compass_direction = Nordabweichung auf dem Grundriss (für Kompassrose)
     * ventilation_direction = tatsächliche Blickrichtung (Facing) des Gebäudes
     * sitting_direction = Gegenrichtung (180° von Facing)
     */
    public function getActiveDirection(): ?float
    {
        // ventilation_direction stores the actual facing direction (Blickrichtung)
        // compass_direction stores the North deviation on the floor plan (for compass rose only)
        return $this->use_facing_direction
           ? $this->ventilation_direction
           : $this->ventilation_direction; // Both paths now use ventilation_direction as it's the facing
    }

    /**
     * Gibt die Nordabweichung auf dem Grundriss zurück (für Kompassrose)
     */
    public function getNorthDirection(): ?float
    {
        return $this->compass_direction;
    }

    public function getSittingDirection(): ?float
    {
        return $this->sitting_direction;
    }
}
