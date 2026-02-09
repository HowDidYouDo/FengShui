<?php
// app/models/user.php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

// Für Filament Zugangskontrolle

// Interface

// Trait

// Trait

class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use
        HasFactory,
        Notifiable,
        TwoFactorAuthenticatable,
        HasRoles,
        InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'birth_place',
        'birth_date',
        'birth_time',
        'gender',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    public function hasFeature(string $featureCode): bool
    {
        // Admin darf vielleicht alles? Wenn ja:
        if ($this->hasRole('admin')) return true;

        // 1. Direkter Check
        $userFeature = $this->features()
            ->whereHas('feature', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        if ($userFeature && $userFeature->isValid()) {
            return true;
        }

        // 2. Indirekter Check: Hat der User ein Modul, welches dieses Modul beinhaltet?
        $isIncluded = $this->features()
            ->whereHas('feature.includes', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        return $isIncluded ? $isIncluded->isValid() : false;
    }

    // Beziehung zu den Features

    public function features(): HasMany
    {
        return $this->hasMany(UserFeature::class);
    }

    // Der "magische" Check: Hat der User dieses Feature gültig?

    /**
     * Prüft, ob noch Kontingent für eine Aktion verfügbar ist.
     */
    public function hasAvailableQuota(string $featureCode, int $currentUsage): bool
    {
        $quota = $this->getFeatureQuota($featureCode);

        if ($quota === null) {
            return true;
        }

        return $currentUsage < $quota;
    }

    /**
     * Ermittelt das gesamte Kontingent für ein bestimmtes Feature.
     * Gibt null zurück, wenn unbegrenzt (Admin oder Quota-Feld null).
     */
    public function getFeatureQuota(string $featureCode): ?int
    {
        if ($this->hasRole('admin')) {
            return null;
        }

        $userFeatures = $this->features()
            ->whereHas('feature', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        if ($userFeatures->isEmpty()) {
            return 0;
        }

        // Wenn ein Eintrag null (unbegrenzt) hat, ist das Gesamtkontingent unbegrenzt
        foreach ($userFeatures as $uf) {
            if ($uf->quota === null) {
                return null;
            }
        }

        return $userFeatures->sum('quota');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Nur Admins dürfen ins Admin-Panel ('admin'), alle anderen müssen draußen bleiben.
        // Achtung: Stelle sicher, dass deine Filament-ID in der Config 'admin' heißt (Standard).
        if ($panel->getId() === 'admin') {
            return $this->hasRole('admin');
            // Wenn du dich selbst erstmal einloggen willst bevor Rollen da sind,
            // nutze vorübergehend: return str_ends_with($this->email, '@deine-domain.de');
        }

        return true;
    }

    // Filament Zugangskontrolle (Wer darf ins Admin Panel?)

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }
}
