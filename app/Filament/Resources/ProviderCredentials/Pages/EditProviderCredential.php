<?php

namespace App\Filament\Resources\ProviderCredentials\Pages;

use App\Enums\TranslationProvider;
use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\ProviderCredentials\ProviderCredentialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProviderCredential extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = ProviderCredentialResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $provider = TranslationProvider::from($data['provider']);
        $data['model'] = ($data['model'] ?? null) ?: $provider->defaultModel();
        $data['base_url'] = ($data['base_url'] ?? null) ?: $provider->defaultBaseUrl();

        if ($provider === TranslationProvider::GOOGLE) {
            $data['api_key'] = null;
            $data['model'] = null;
            $data['base_url'] = null;
        } else {
            $data['service_account'] = null;
            $data['google_project_id'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->projects()->exists()),
        ];
    }

    protected function afterSave(): void
    {
        $this->logAdminActivity(
            description: sprintf('Updated provider credential "%s".', $this->record->name),
            subject: $this->record,
            event: 'updated',
        );
    }
}
