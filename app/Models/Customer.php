<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'billing_street',
        'billing_zip',
        'billing_city',
        'billing_country',
        'birth_date',
        'birth_time',
        'birth_place',
        'gender',
        'notes',
        'is_self_profile',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_self_profile' => 'boolean',
        // birth_time lassen wir als string, da Carbon bei reinen Zeitfeldern manchmal zickt,
        // kann aber bei Bedarf zu 'datetime:H:i' geändert werden.
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (!$customer->user_id && auth()->check()) {
                $customer->user_id = auth()->id();
            }
        });
    }

    // Der Consultant, dem dieser Kunde gehört
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    // Raumzuweisungen für den Kunden selbst
    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
