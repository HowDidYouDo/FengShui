<?php

namespace App\Models;

use App\Traits\HasMingGua;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasMingGua;

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
        'life_gua',
        'kua_group',
        'notes',
        'is_self_profile',
    ];

    protected function casts(): array
    {
        return [
            'birth_place' => 'encrypted',
            'billing_street' => 'encrypted',
            'is_self_profile' => 'boolean',
            'life_gua' => 'integer',
        ];
    }

    public function getBirthDateAttribute($value)
    {
        if (!$value) return null;

        $decrypted = null;

        try {
            // 1. Try decrypting (WITHOUT unserialization - our current standard)
            $decrypted = decrypt($value, false);
        } catch (\Exception $e) {
            try {
                // 2. Try decrypting (WITH unserialization - fallback for old encrypt() data)
                $decrypted = decrypt($value);
            } catch (\Exception $e2) {
                // 3. Fallback: it might not be encrypted at all (very old data)
                if (is_string($value) && str_starts_with($value, 'eyJpdi')) {
                    return null;
                }
                $decrypted = $value;
            }
        }

        try {
            return $decrypted ? \Illuminate\Support\Carbon::parse($decrypted) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setBirthDateAttribute($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        // Use non-serialized encryption for consistency with built-in 'encrypted' cast
        $this->attributes['birth_date'] = $value ? encrypt($value, false) : null;
    }

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
