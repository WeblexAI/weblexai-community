<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->loadMissing(['owner', 'originalLanguage', 'languages', 'acceptedOrigins', 'collaborators']);
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
