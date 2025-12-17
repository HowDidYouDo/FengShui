<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        // Wir laden den Customer eager, um N+1 zu vermeiden, falls möglich.
        // Aber hier ist Sicherheit wichtiger.

        // Check: Gehört der Kunde dieses Projekts dem eingeloggten User?
        return $project->customer && $project->customer->user_id === $user->id;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->customer && $project->customer->user_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->customer && $project->customer->user_id === $user->id;
    }
}
