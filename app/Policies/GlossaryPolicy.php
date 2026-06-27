<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Glossary;
use App\Models\Project;
use App\Models\User;

class GlossaryPolicy
{
    public function manage(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageContent());
    }

    public function delete(User $user, Glossary $glossary): bool
    {
        return CollaboratorRole::isAuthorized($glossary->project, $user, CollaboratorRole::canManageContent());
    }
}
