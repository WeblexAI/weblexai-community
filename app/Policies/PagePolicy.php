<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Page;
use App\Models\Project;
use App\Models\User;

class PagePolicy
{
    public function toggleBlacklist(User $user, Page $page): bool
    {
        return CollaboratorRole::isAuthorized($page->project, $user, CollaboratorRole::canManageSettings());
    }

    public function toggleBulkBlacklist(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }
}
