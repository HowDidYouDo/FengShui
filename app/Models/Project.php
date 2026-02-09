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
        'facing_direction',
        'ventilation_direction',
        'period',
        'use_facing_direction',
        'facing_mountain',
        'is_replacement_chart',
        'special_chart_type',
    ];

    protected $casts = [
        'settled_year' => 'integer',
        'facing_direction' => 'decimal:4',
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
     * Gibt die aktive Direction zurück (Facing ODER Ventilation)
     */
    public function getActiveDirection(): ?float
    {
        return $this->use_facing_direction
            ? $this->facing_direction
            : $this->ventilation_direction;
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
}
