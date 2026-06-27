<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use LogsAdminActivity;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logAdminActivity(
            description: sprintf('Created project "%s".', $this->record->name),
            subject: $this->record,
            event: 'created',
        );
    }
}
