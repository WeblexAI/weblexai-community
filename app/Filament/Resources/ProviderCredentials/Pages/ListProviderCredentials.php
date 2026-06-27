<?php

namespace App\Filament\Resources\ProviderCredentials\Pages;

use App\Filament\Resources\ProviderCredentials\ProviderCredentialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviderCredentials extends ListRecords
{
    protected static string $resource = ProviderCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
