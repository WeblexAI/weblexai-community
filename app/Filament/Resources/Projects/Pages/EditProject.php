<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Projects\ProjectResource;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditProject extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rotateApiKey')
                ->label('Rotate API key')
                ->requiresConfirmation()
                ->action(function (): void {
                    try {
                        ProjectService::rotateApiKey($this->record);
                        $this->logAdminActivity(
                            description: sprintf('Rotated API key for project "%s".', $this->record->name),
                            subject: $this->record,
                            event: 'updated',
                        );
                        Notification::make()
                            ->title('Project API key rotated')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        Log::error('Failed to rotate project API key.', [
                            'project_id' => $this->record->id,
                            'error' => $exception->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Could not rotate API key')
                            ->body('Check the application logs for details.')
                            ->danger()
                            ->send();
                    }
                }),
            ViewAction::make(),
            DeleteAction::make()
                ->after(function (): void {
                    $this->logAdminActivity(
                        description: sprintf('Deleted project "%s".', $this->record->name),
                        subject: $this->record,
                        event: 'deleted',
                    );
                }),
        ];
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: sprintf('Updated project "%s".', $this->record->name),
            subject: $this->record,
            event: 'updated',
        );
    }
}
