<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Languages\LanguageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(function (): void {
                    $this->logAdminActivity(
                        description: sprintf('Deleted language "%s".', $this->record->name),
                        subject: $this->record,
                        event: 'deleted',
                    );
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: sprintf('Updated language "%s".', $this->record->name),
            subject: $this->record,
            event: 'updated',
        );
    }
}
