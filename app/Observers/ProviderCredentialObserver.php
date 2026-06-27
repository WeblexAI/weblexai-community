<?php

namespace App\Observers;

use App\Models\ProviderCredential;
use Illuminate\Support\Str;

class ProviderCredentialObserver
{
    public function creating(ProviderCredential $credential): void
    {
        $credential->uuid ??= Str::uuid()->toString();
    }
}
