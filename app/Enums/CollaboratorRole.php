<?php

namespace App\Enums;

use App\Models\Project;
use App\Models\User;

enum CollaboratorRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case TRANSLATOR = 'translator';
    case VIEWER = 'viewer';

    public static function assignable(): array
    {
        return [
            self::MANAGER->value => 'Manager',
            self::TRANSLATOR->value => 'Translator',
            self::VIEWER->value => 'Viewer',
        ];
    }

    public static function canManageSettings(): array
    {
        return [self::OWNER, self::MANAGER];
    }

    public static function canManageMembers(): array
    {
        return [self::OWNER, self::MANAGER];
    }

    public static function canManageContent(): array
    {
        return [self::OWNER, self::MANAGER, self::TRANSLATOR];
    }

    public static function isAuthorized(Project $project, User $user, array $roles): bool
    {
        if ($project->user_id === $user->id) {
            return in_array(self::OWNER, $roles, true);
        }

        $membership = $project->collaborators()
            ->where('users.id', $user->id)
            ->first();

        return $membership !== null
            && in_array($membership->pivot->role, $roles, true);
    }
}
