<?php

namespace App\Support\Installation;

use PDO;
use Redis;
use Throwable;

class RequirementsChecker
{
    public function system(): array
    {
        $checks = [
            $this->check('PHP 8.3 or newer', version_compare(PHP_VERSION, '8.3.0', '>='), PHP_VERSION),
        ];

        foreach (['curl', 'intl', 'json', 'mbstring', 'openssl', 'pdo_pgsql', 'redis', 'zip'] as $extension) {
            $checks[] = $this->check("PHP extension: {$extension}", extension_loaded($extension), 'Install or enable the extension.');
        }

        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            $checks[] = $this->check("Writable: {$path}", is_writable($path), 'Grant the web process write access.');
        }

        $envPath = is_file(base_path('.env')) ? base_path('.env') : base_path();
        $checks[] = $this->check('Writable environment configuration', is_writable($envPath), 'Make .env or the project directory writable during installation.');

        return $checks;
    }

    public function infrastructure(array $input): array
    {
        return [
            $this->postgres($input),
            $this->redis($input),
        ];
    }

    private function postgres(array $input): array
    {
        try {
            $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $input['db_host'], $input['db_port'], $input['db_database']);
            $pdo = new PDO($dsn, $input['db_username'], $input['db_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $version = (int) $pdo->query('SHOW server_version_num')->fetchColumn();

            return $this->check('PostgreSQL 14 or newer', $version >= 140000, 'Detected '.($version ?: 'unknown'));
        } catch (Throwable) {
            return $this->check('PostgreSQL connection', false, 'Check the host, port, database, username, password, and network access.');
        }
    }

    private function redis(array $input): array
    {
        try {
            $redis = new Redis;
            $redis->connect($input['redis_host'], (int) $input['redis_port'], 5);
            if (($input['redis_password'] ?? '') !== '') {
                $redis->auth($input['redis_password']);
            }
            $redis->select((int) $input['redis_db']);
            $version = $redis->info('server')['redis_version'] ?? '0';
            $redis->close();

            return $this->check('Redis 6 or newer', version_compare($version, '6.0.0', '>='), "Detected {$version}");
        } catch (Throwable) {
            return $this->check('Redis connection', false, 'Check the host, port, password, database, and network access.');
        }
    }

    private function check(string $name, bool $passed, string $detail): array
    {
        return compact('name', 'passed', 'detail');
    }
}
