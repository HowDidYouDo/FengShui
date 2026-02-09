<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaguaNote extends Model
{
    protected $fillable = [
        'floor_plan_id',
        'gua_number',
        'mountain_star',
        'water_star',
        'base_star',
        'yearly_star',
        'monthly_star',
        'room_type',
        'content',
        'stars_analysis',
    ];

    protected $casts = [
        'gua_number' => 'integer',
        'mountain_star' => 'integer',
        'water_star' => 'integer',
        'base_star' => 'integer',
        'yearly_star' => 'integer',
        'monthly_star' => 'integer',
    ];

    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
