<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must run from the command line.\n");
    exit(1);
}

$keypair = sodium_crypto_sign_keypair();

echo 'RELEASE_PRIVATE_KEY='.base64_encode(sodium_crypto_sign_secretkey($keypair)).PHP_EOL;
echo 'RELEASE_PUBLIC_KEY='.base64_encode(sodium_crypto_sign_publickey($keypair)).PHP_EOL;
