<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Feature;
use App\Models\User;
use App\Models\UserFeature;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // 1. Suche alle Features, die als Standard markiert sind
        $defaultFeatures = Feature::where('is_default', true)
            ->where('active', true)
            ->get();

        // 2. Lege für jedes Feature eine UserFeature-Lizenz an
        foreach ($defaultFeatures as $feature) {
            UserFeature::create([
                'user_id' => $user->id,
                'feature_id' => $feature->id,
                'active' => true,
                'expires_at' => null, // Unbegrenzt
                'quota' => null,      // Unbegrenzt
            ]);
        }

        Customer::create([
            'user_id' => $user->id, // Gehört zu sich selbst
            'name' => $user->name,
            'email' => $user->email,
            'birth_date' => $user->birth_date, // Falls schon vorhanden
            'birth_time' => $user->birth_time,
            'birth_place' => $user->birth_place,
            'gender' => $user->gender,
            'notes' => 'Your own profile (Main User).',
            'is_self_profile' => true,
        ]);

    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Wenn sich Stammdaten ändern, update auch das verknüpfte "Selbst-Profil"
        if ($user->isDirty(['name', 'birth_date', 'birth_time', 'birth_place', 'gender'])) {
            // Wir finden das Profil anhand der ID und einer Konvention (z.B. erstes Profil)
            // Oder wir speichern die 'self_customer_id' am User (perfekt).

            // Einfacher: Suche Customer, der user_id == user->id UND email == user->email ist (für den Start)
            $selfProfile = Customer::where('user_id', $user->id)
                ->where('is_self_profile', true)
                ->first();

            if ($selfProfile) {
                $selfProfile->update([
                    'name' => $user->name,
                    'birth_date' => $user->birth_date,
                    'birth_time' => $user->birth_time,
                    'birth_place' => $user->birth_place,
                    'gender' => $user->gender,
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
