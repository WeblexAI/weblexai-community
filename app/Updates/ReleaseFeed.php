<?php

namespace App\Updates;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Http;
use PDO;
use Redis;
use RuntimeException;
use Throwable;

class ReleaseFeed
{
    public function __construct(
        private readonly Repository $cache,
        private readonly ReleaseSignatureVerifier $verifier,
    ) {}

    public function latest(bool $force = false): ?ReleaseManifest
    {
        if ($force) {
            $this->cache->forget('community:release-feed:stable');
        }

        return $this->cache->remember(
            'community:release-feed:stable',
            now()->addHours(config('community.update_check_hours')),
            function () {
                $url = config('community.release_feed_url');
                if (! $url) {
                    return null;
                }

                $response = Http::acceptJson()->timeout(10)->retry(2, 250)->get($url);
                if (! $response->successful()) {
                    throw new RuntimeException('The release feed could not be retrieved.');
                }

                $release = ReleaseManifest::fromArray($response->json());
                $this->verifier->verify($release);

                return $release;
            },
        );
    }

    public function isCompatible(ReleaseManifest $release): bool
    {
        $requirements = $release->requirements;

        return version_compare(PHP_VERSION, $requirements['php'] ?? '0', '>=')
            && version_compare((string) config('community.version'), $requirements['application'] ?? '0', '>=')
            && version_compare($this->postgresVersion(), $requirements['postgres'] ?? '0', '>=')
            && version_compare($this->redisVersion(), $requirements['redis'] ?? '0', '>=');
    }

    public function updateAvailable(ReleaseManifest $release): bool
    {
        return version_compare($release->version, (string) config('community.version'), '>');
    }

    private function postgresVersion(): string
    {
        try {
            $database = config('database.connections.pgsql');
            $pdo = new PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $database['host'], $database['port'], $database['database']),
                $database['username'],
                $database['password'],
                [PDO::ATTR_TIMEOUT => 3],
            );

            return (string) $pdo->query('SHOW server_version')->fetchColumn();
        } catch (Throwable) {
            return '0';
        }
    }

    private function redisVersion(): string
    {
        try {
            $connection = config('database.redis.default');
            $redis = new Redis;
            $redis->connect($connection['host'], (int) $connection['port'], 3);
            if (! empty($connection['password'])) {
                $redis->auth($connection['password']);
            }
            $version = $redis->info('server')['redis_version'] ?? '0';
            $redis->close();

            return $version;
        } catch (Throwable) {
            return '0';
        }
    }
}
