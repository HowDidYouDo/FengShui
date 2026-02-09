<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFeature extends Model
{
    protected $fillable = [
        'user_id',
        'feature_id',
        'quota',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'quota' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    // Hilfsmethode: Ist das Feature noch gültig?
    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Optional: Quota-Check könnte hier auch rein,
        // aber Quota prüft man meistens beim "Verbrauchen" (z.B. createClient).

        return true;
    }
}
