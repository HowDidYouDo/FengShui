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
        'content',
    ];

    protected $casts = [
        'gua_number' => 'integer',
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
