<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyMember extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'relationship',
        'birth_date',
        'birth_time',
        'birth_place',
        'gender',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
