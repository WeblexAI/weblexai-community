<?php

namespace App\Observers;

use App\Models\ProviderCredential;
use App\Services\ProjectRevisionService;
use Illuminate\Support\Str;

class ProviderCredentialObserver
{
    public function creating(ProviderCredential $credential): void
    {
        $credential->uuid ??= Str::uuid()->toString();
    }

    public function updated(ProviderCredential $credential): void
    {
        foreach ($credential->projects()->pluck('id') as $projectId) {
            app(ProjectRevisionService::class)->bumpGeneration($projectId);
        }
    }

    public function deleting(ProviderCredential $credential): void
    {
        foreach ($credential->projects()->pluck('id') as $projectId) {
            app(ProjectRevisionService::class)->bumpGeneration($projectId);
        }
    }
}
