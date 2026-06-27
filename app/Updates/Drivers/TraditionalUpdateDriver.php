<?php

namespace App\Updates\Drivers;

use App\Updates\Contracts\UpdateDriver;
use App\Updates\ReleaseManifest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;

class TraditionalUpdateDriver implements UpdateDriver
{
    public function name(): string
    {
        return 'traditional';
    }

    public function apply(ReleaseManifest $release): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            throw new RuntimeException('Traditional one-click updates require Linux.');
        }

        $base = rtrim((string) config('community.update_base_path'), '/');
        $current = $base.'/current';
        $releases = $base.'/releases';
        $target = $releases.'/'.$release->version;

        if (! is_link($current) || ! is_dir($releases) || is_dir($target)) {
            throw new RuntimeException('The release directory layout is invalid or the target version already exists.');
        }

        $archive = storage_path("app/updates/weblex-{$release->version}.tar.gz");
        if (! is_dir(dirname($archive))) {
            mkdir(dirname($archive), 0770, true);
        }

        $response = Http::timeout(120)->sink($archive)->get($release->artifactUrl);
        if (! $response->successful() || hash_file('sha256', $archive) !== $release->artifactSha256) {
            @unlink($archive);
            throw new RuntimeException('The release artifact download or checksum verification failed.');
        }

        mkdir($target, 0755, true);
        $this->run(['tar', '-xzf', $archive, '-C', $target, '--strip-components=1']);
        $previous = readlink($current);

        $this->run([
            'sh',
            base_path('scripts/backup-traditional.sh'),
            (string) config('community.update_backup_path'),
            base_path(),
        ]);
        $this->run([PHP_BINARY, base_path('artisan'), 'down', '--retry=30']);
        try {
            if (is_dir($target.'/storage')) {
                $this->run(['rm', '-rf', $target.'/storage']);
            }
            $this->run(['ln', '-sfn', base_path('.env'), $target.'/.env']);
            $this->run(['ln', '-sfn', storage_path(), $target.'/storage']);
            $this->run([PHP_BINARY, $target.'/artisan', 'migrate', '--force']);
            $this->run(['ln', '-sfn', $target, $current]);
            $this->run([PHP_BINARY, $target.'/artisan', 'optimize:clear']);
            $this->run([PHP_BINARY, $target.'/artisan', 'horizon:terminate']);
        } catch (\Throwable $exception) {
            $this->run(['ln', '-sfn', $previous, $current]);
            throw $exception;
        } finally {
            $this->run([PHP_BINARY, $current.'/artisan', 'up']);
            @unlink($archive);
        }
    }

    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(600)->mustRun();
    }
}
