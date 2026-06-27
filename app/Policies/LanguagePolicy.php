<?php

namespace App\Policies;

use App\Enums\CollaboratorRole;
use App\Models\Language;
use App\Models\Project;
use App\Models\User;

class LanguagePolicy
{
    public function manage(User $user, Project $project): bool
    {
        return CollaboratorRole::isAuthorized($project, $user, CollaboratorRole::canManageSettings());
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Language $language): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Language $language): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Language $language): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Language $language): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Language $language): bool
    {
        return $user->hasRole('admin');
    }
}
