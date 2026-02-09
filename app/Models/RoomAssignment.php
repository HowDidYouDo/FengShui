<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAssignment extends Model
{
    protected $fillable = [
        'bagua_note_id',
        'customer_id',
        'family_member_id',
        'usage_type',
        'person_facing_direction',
        'suitability_rating',
    ];

    protected $casts = [
        'person_facing_direction' => 'decimal:2',
        'suitability_rating' => 'integer',
    ];

    public function baguaNote(): BelongsTo
    {
        return $this->belongsTo(BaguaNote::class);
    }

    // Die Person kann ENTWEDER ein Kunde ODER ein Familienmitglied sein.

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    /**
     * Helper: Gibt das Model der zugewiesenen Person zurück (Customer oder FamilyMember).
     */
    public function getPersonAttribute()
    {
        return $this->customer ?? $this->familyMember;
    }
}
