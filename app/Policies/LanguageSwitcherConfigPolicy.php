<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Project;
use App\Models\User;

class LanguageSwitcherConfigPolicy
{
    public function view(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }

    public function update(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }
}
