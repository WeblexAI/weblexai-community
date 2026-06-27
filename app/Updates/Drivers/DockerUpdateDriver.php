<?php

namespace App\Updates\Drivers;

use App\Updates\Contracts\UpdateDriver;
use App\Updates\ReleaseManifest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DockerUpdateDriver implements UpdateDriver
{
    public function name(): string
    {
        return 'docker';
    }

    public function apply(ReleaseManifest $release): void
    {
        $url = config('community.update_agent_url');
        $secret = (string) config('community.update_agent_secret');

        if (! $url || strlen($secret) < 32) {
            throw new RuntimeException('The Docker update agent is not configured.');
        }

        $body = json_encode(['version' => $release->version], JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        $response = Http::withHeaders([
            'X-Weblex-Timestamp' => $timestamp,
            'X-Weblex-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->timeout(15)->withBody($body, 'application/json')->post(rtrim($url, '/').'/update');

        if (! $response->successful()) {
            throw new RuntimeException('The Docker update agent rejected the update request.');
        }
    }
}
