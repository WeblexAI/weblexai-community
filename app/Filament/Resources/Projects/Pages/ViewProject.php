<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Projects\ProjectResource;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewProject extends ViewRecord
{
    use LogsAdminActivity;

    protected static string $resource = ProjectResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing(['owner', 'originalLanguage', 'languages', 'acceptedOrigins', 'collaborators']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rotateApiKey')
                ->label('Rotate API key')
                ->authorize(fn (): bool => auth()->user()?->can('update', $this->record) ?? false)
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
            EditAction::make(),
        ];
    }
}
