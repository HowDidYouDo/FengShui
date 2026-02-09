<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;

class FamilyMember extends Model
{
    public const RELATIONSHIP_PRIMARY_PARTNER = 'primary_partner';
    public const RELATIONSHIP_SECONDARY_PARTNER = 'secondary_partner';
    public const RELATIONSHIP_CHILD = 'child';
    public const RELATIONSHIP_PARENT = 'parent';
    public const RELATIONSHIP_GRANDPARENT = 'grandparent';
    public const RELATIONSHIP_GRANDCHILD = 'grandchild';

    protected $fillable = [
        'customer_id',
        'name',
        'relationship',
        'birth_date',
        'birth_time',
        'birth_place',
        'gender',
    ];

    public static function getRelationships(): array
    {
        return [
            self::RELATIONSHIP_PRIMARY_PARTNER => __('Primary Partner'),
            self::RELATIONSHIP_SECONDARY_PARTNER => __('Secondary Partner'),
            self::RELATIONSHIP_CHILD => __('Child'),
            self::RELATIONSHIP_PARENT => __('Parent'),
            self::RELATIONSHIP_GRANDPARENT => __('Grandparent'),
            self::RELATIONSHIP_GRANDCHILD => __('Grandchild'),
        ];
    }

    public function getRelationshipLabel(): string
    {
        if (!$this->relationship) {
            return '';
        }

        $labels = self::getRelationships();

        if (!isset($labels[$this->relationship])) {
            return $this->relationship;
        }

        // Gender specific labels
        if ($this->relationship === self::RELATIONSHIP_PRIMARY_PARTNER) {
            return $this->gender === 'f' ? __('Primary Partner (female)') : __('Primary Partner (male)');
        }
        if ($this->relationship === self::RELATIONSHIP_SECONDARY_PARTNER) {
            return $this->gender === 'f' ? __('Secondary Partner (female)') : __('Secondary Partner (male)');
        }
        if ($this->relationship === self::RELATIONSHIP_PARENT) {
            return $this->gender === 'f' ? __('Mother') : __('Father');
        }
        if ($this->relationship === self::RELATIONSHIP_GRANDPARENT) {
            return $this->gender === 'f' ? __('Grandmother') : __('Grandfather');
        }
        if ($this->relationship === self::RELATIONSHIP_GRANDCHILD) {
            return $this->gender === 'f' ? __('Granddaughter') : __('Grandson');
        }
        if ($this->relationship === self::RELATIONSHIP_CHILD) {
            return $this->gender === 'f' ? __('Daughter') : __('Son');
        }

        return $labels[$this->relationship];
    }

    protected function casts(): array
    {
        return [
            'birth_place' => 'encrypted',
        ];
    }

    public function getBirthDateAttribute($value)
    {
        if (!$value) return null;

        $decrypted = null;

        try {
            // 1. Try decrypting (with unserialization - standard for encrypt() helper)
            $decrypted = decrypt($value);
        } catch (\Exception $e) {
            try {
                // 2. Try decrypting (without unserialization - standard for 'encrypted' cast)
                $decrypted = decrypt($value, false);
            } catch (\Exception $e2) {
                // 3. Fallback: it might not be encrypted at all (old data)
                if (is_string($value) && str_starts_with($value, 'eyJpdi')) {
                    // It looks encrypted but we can't decrypt it (e.g. wrong APP_KEY)
                    return null;
                }
                $decrypted = $value;
            }
        }

        try {
            return $decrypted ? Carbon::parse($decrypted) : null;
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }
}
