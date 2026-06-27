<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Project;
use App\Models\User;

class TranslationPolicy
{
    public function export(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }

    public function import(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }

    public function update(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }
}
