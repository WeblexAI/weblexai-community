<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::toArray() as $role) {
            Role::findOrCreate($role);
        }

        $backupPermissions = [
            'create-backup',
            'download-backup',
            'delete-backup',
        ];

        foreach ($backupPermissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate(UserRole::ADMIN->value)->givePermissionTo($backupPermissions);
    }
}
