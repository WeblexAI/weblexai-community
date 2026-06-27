<?php

namespace App\Filament\Resources\ProviderCredentials\Pages;

use App\Enums\TranslationProvider;
use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\ProviderCredentials\ProviderCredentialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProviderCredential extends CreateRecord
{
    use LogsAdminActivity;

    protected static string $resource = ProviderCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $provider = TranslationProvider::from($data['provider']);
        $data['user_id'] = auth()->id();
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

    protected function afterCreate(): void
    {
        $this->logAdminActivity(
            description: sprintf('Created provider credential "%s".', $this->record->name),
            subject: $this->record,
            event: 'created',
        );
    }
}
