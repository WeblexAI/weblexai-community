<?php

namespace App\Updates;

use RuntimeException;

class ReleaseSignatureVerifier
{
    public function verify(ReleaseManifest $release): void
    {
        $publicKey = base64_decode((string) config('community.release_public_key'), true);
        $signature = base64_decode($release->signature, true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('RELEASE_PUBLIC_KEY is missing or invalid.');
        }

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $release->signedPayload(), $publicKey)) {
            throw new RuntimeException('Release metadata signature verification failed.');
        }
    }
}
