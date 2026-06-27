<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Languages\LanguageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLanguage extends CreateRecord
{
    use LogsAdminActivity;

    protected static string $resource = LanguageResource::class;

    protected function afterCreate(): void
    {
        $this->logAdminActivity(
            description: sprintf('Created language "%s".', $this->record->name),
            subject: $this->record,
            event: 'created',
        );
    }
}
