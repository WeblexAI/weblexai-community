<?php

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must run from the command line.\n");
    exit(1);
}

$options = getopt('', [
    'version:',
    'artifact-url:',
    'sha256:',
    'notes-url:',
    'output:',
    'security::',
]);

foreach (['version', 'artifact-url', 'sha256', 'notes-url', 'output'] as $required) {
    if (blank_arg($options[$required] ?? null)) {
        fwrite(STDERR, "Missing --{$required}.\n");
        exit(1);
    }
}

if (! preg_match('/^\d+\.\d+\.\d+$/', $options['version'])) {
    fwrite(STDERR, "Release version must use semantic versioning.\n");
    exit(1);
}

if (! preg_match('/^[a-f0-9]{64}$/i', $options['sha256'])) {
    fwrite(STDERR, "Artifact SHA-256 must be 64 hexadecimal characters.\n");
    exit(1);
}

$privateKey = base64_decode((string) getenv('RELEASE_PRIVATE_KEY'), true);
if ($privateKey === false || strlen($privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
    fwrite(STDERR, "RELEASE_PRIVATE_KEY must be a base64 encoded Ed25519 secret key.\n");
    exit(1);
}

$payload = [
    'version' => $options['version'],
    'channel' => 'stable',
    'published_at' => gmdate('c'),
    'notes_url' => $options['notes-url'],
    'security' => filter_var($options['security'] ?? false, FILTER_VALIDATE_BOOL),
    'requirements' => [
        'application' => getenv('RELEASE_REQUIRES_APP') ?: '1.0.0',
        'php' => getenv('RELEASE_REQUIRES_PHP') ?: '8.4.0',
        'postgres' => getenv('RELEASE_REQUIRES_POSTGRES') ?: '14.0',
        'redis' => getenv('RELEASE_REQUIRES_REDIS') ?: '6.0',
    ],
    'artifact' => [
        'url' => $options['artifact-url'],
        'sha256' => strtolower($options['sha256']),
    ],
];

$signedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
$manifest = [
    ...$payload,
    'signature' => base64_encode(sodium_crypto_sign_detached($signedPayload, $privateKey)),
];

file_put_contents(
    $options['output'],
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
);

function blank_arg(mixed $value): bool
{
    return ! is_string($value) || trim($value) === '';
}
