<?php
// app/models/user.php
namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Filament\Models\Contracts\FilamentUser; // Für Filament Zugangskontrolle
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia; // Interface
use Spatie\MediaLibrary\InteractsWithMedia; // Trait
use Spatie\Permission\Traits\HasRoles; // Trait

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

    // Beziehung zu den Features
    public function features(): HasMany
    {
        return $this->hasMany(UserFeature::class);
    }

    // Der "magische" Check: Hat der User dieses Feature gültig?
    public function hasFeature(string $featureCode): bool
    {
        // Admin darf vielleicht alles? Wenn ja:
        if ($this->hasRole('admin')) return true;

        // Wir suchen in den User-Features, ob eines dabei ist,
        // das mit dem Feature-Katalog verknüpft ist, welcher den Code hat.
        $userFeature = $this->features()
            ->whereHas('feature', function ($query) use ($featureCode) {
                $query->where('code', $featureCode);
            })
            ->first();

        return $userFeature ? $userFeature->isValid() : false;
    }

    // Filament Zugangskontrolle (Wer darf ins Admin Panel?)
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

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
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
}
