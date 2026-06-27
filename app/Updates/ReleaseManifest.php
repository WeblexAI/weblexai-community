<?php

namespace App\Updates;

use InvalidArgumentException;

readonly class ReleaseManifest
{
    public function __construct(
        public string $version,
        public string $channel,
        public string $publishedAt,
        public string $notesUrl,
        public bool $security,
        public array $requirements,
        public string $artifactUrl,
        public string $artifactSha256,
        public string $signature,
    ) {}

    public static function fromArray(array $data): self
    {
        foreach (['version', 'channel', 'published_at', 'notes_url', 'security', 'requirements', 'artifact', 'signature'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Release metadata is missing {$key}.");
            }
        }

        if (($data['channel'] ?? null) !== 'stable') {
            throw new InvalidArgumentException('Only the stable release channel is supported.');
        }

        if (! preg_match('/^\d+\.\d+\.\d+$/', $data['version'])) {
            throw new InvalidArgumentException('Release version must use semantic versioning.');
        }

        if (! isset($data['artifact']['url'], $data['artifact']['sha256'])
            || ! preg_match('/^[a-f0-9]{64}$/i', $data['artifact']['sha256'])) {
            throw new InvalidArgumentException('Release artifact metadata is invalid.');
        }

        return new self(
            version: $data['version'],
            channel: $data['channel'],
            publishedAt: $data['published_at'],
            notesUrl: $data['notes_url'],
            security: (bool) $data['security'],
            requirements: $data['requirements'],
            artifactUrl: $data['artifact']['url'],
            artifactSha256: strtolower($data['artifact']['sha256']),
            signature: $data['signature'],
        );
    }

    public function signedPayload(): string
    {
        return json_encode([
            'version' => $this->version,
            'channel' => $this->channel,
            'published_at' => $this->publishedAt,
            'notes_url' => $this->notesUrl,
            'security' => $this->security,
            'requirements' => $this->requirements,
            'artifact' => [
                'url' => $this->artifactUrl,
                'sha256' => $this->artifactSha256,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
