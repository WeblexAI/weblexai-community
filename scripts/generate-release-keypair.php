<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must run from the command line.\n");
    exit(1);
}

if (! function_exists('sodium_crypto_sign_keypair')) {
    fwrite(STDERR, "The PHP sodium extension is required to generate release signing keys.\n");
    fwrite(STDERR, "Run with Docker instead:\n");
    fwrite(STDERR, "docker run --rm -v \"\${PWD}:/app\" -w /app composer:2.8.9 php scripts/generate-release-keypair.php\n");
    exit(1);
}

$keypair = sodium_crypto_sign_keypair();

echo 'RELEASE_PRIVATE_KEY='.base64_encode(sodium_crypto_sign_secretkey($keypair)).PHP_EOL;
echo 'RELEASE_PUBLIC_KEY='.base64_encode(sodium_crypto_sign_publickey($keypair)).PHP_EOL;
