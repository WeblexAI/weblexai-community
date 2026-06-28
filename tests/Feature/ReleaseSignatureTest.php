<?php

use App\Updates\ReleaseManifest;
use App\Updates\ReleaseSignatureVerifier;

function signedRelease(array $overrides = []): array
{
    $release = array_replace_recursive([
        'version' => '1.0.1',
        'channel' => 'stable',
        'published_at' => '2026-06-07T00:00:00Z',
        'notes_url' => 'https://example.com/releases/1.0.1',
        'security' => false,
        'requirements' => [
            'application' => '1.0.0',
            'php' => '8.3.0',
            'postgres' => '14.0',
            'redis' => '6.0',
        ],
        'artifact' => [
            'url' => 'https://example.com/releases/weblex-1.0.1.tar.gz',
            'sha256' => str_repeat('a', 64),
        ],
        'signature' => '',
    ], $overrides);

    return $release;
}

it('accepts authentic release metadata and rejects tampering', function () {
    if (! function_exists('sodium_crypto_sign_keypair')) {
        $this->markTestSkipped('The sodium extension is required for release signature verification.');
    }

    $keypair = sodium_crypto_sign_keypair();
    config(['community.release_public_key' => base64_encode(sodium_crypto_sign_publickey($keypair))]);
    $release = signedRelease();
    $manifest = ReleaseManifest::fromArray($release);
    $release['signature'] = base64_encode(
        sodium_crypto_sign_detached($manifest->signedPayload(), sodium_crypto_sign_secretkey($keypair)),
    );

    $verifier = app(ReleaseSignatureVerifier::class);
    $verifier->verify(ReleaseManifest::fromArray($release));

    $release['version'] = '1.0.2';
    expect(fn () => $verifier->verify(ReleaseManifest::fromArray($release)))
        ->toThrow(RuntimeException::class, 'signature verification failed');
});

it('rejects unsupported release metadata', function () {
    expect(fn () => ReleaseManifest::fromArray(signedRelease(['channel' => 'beta'])))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => ReleaseManifest::fromArray(signedRelease(['version' => 'latest'])))
        ->toThrow(InvalidArgumentException::class);
});
