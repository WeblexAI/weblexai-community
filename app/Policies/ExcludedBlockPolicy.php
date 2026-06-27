<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\ExcludedBlock;
use App\Models\Project;
use App\Models\User;

class ExcludedBlockPolicy
{
    public function create(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }

    public function update(User $user, ExcludedBlock $block): bool
    {
        return CollaboratorRole::isAuthorized($block->project, $user, CollaboratorRole::canManageContent());
    }

    public function delete(User $user, ExcludedBlock $block): bool
    {
        return CollaboratorRole::isAuthorized($block->project, $user, CollaboratorRole::canManageContent());
    }

    public function bulkDelete(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }
}
