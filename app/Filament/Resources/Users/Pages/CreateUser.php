<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use LogsAdminActivity;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['force_password_change'] = true;

        return $data;
    }

    protected function afterCreate(): void
    {
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
