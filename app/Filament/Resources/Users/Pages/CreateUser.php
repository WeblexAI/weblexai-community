<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    use LogsAdminActivity;

    protected static string $resource = UserResource::class;

    private ?int $selectedRoleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedRoleId = isset($data['system_role']) ? (int) $data['system_role'] : null;
        unset($data['system_role']);

        $data['force_password_change'] = true;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->selectedRoleId) {
            $role = Role::query()->find($this->selectedRoleId);

            if ($role) {
                $this->record->syncRoles([$role->name]);
            }
        }

        $this->logAdminActivity(
            description: sprintf('Created user "%s".', $this->record->email),
            subject: $this->record,
            event: 'created',
        );

        Notification::make()
            ->success()
            ->title('User created')
            ->body('The user must change the temporary password before accessing projects.')
            ->send();
    }
}
