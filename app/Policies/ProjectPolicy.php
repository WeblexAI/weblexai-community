<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasRole('admin')
            || $project->user_id === $user->id
            || $project->collaborators()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasRole('admin')
            || CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole('admin');
    }

    public function manageCollaborators(User $user, Project $project): bool
    {
        return $user->hasRole('admin');
    }

    public function viewActivityLogs(User $user, Project $project): bool
    {
        return $user->hasRole('admin')
            || CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }
}
