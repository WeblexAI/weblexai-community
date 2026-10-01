<?php

use App\Services\CDN\TranslationLeaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (! function_exists('pcntl_fork') || config('cache.default') !== 'redis' || config('database.default') !== 'pgsql') {
        $this->markTestSkipped('This integration test requires Linux pcntl with shared PostgreSQL and Redis.');
    }
});

it('allows exactly one concurrent owner for an identical translation lease', function () {
    $leases = app(TranslationLeaseService::class);
    $key = 'concurrency:'.uniqid();
    $owner = $leases->acquire($key);
    $result = tempnam(sys_get_temp_dir(), 'weblex-lease-');
    expect($owner)->not->toBeNull();
    DB::disconnect();

    $pid = pcntl_fork();
    if ($pid === 0) {
        Cache::clearResolvedInstances();
        $childLease = app(TranslationLeaseService::class)->acquire($key);
        file_put_contents($result, $childLease === null ? 'blocked' : 'acquired');
        app(TranslationLeaseService::class)->release($childLease);
        exit(0);
    }
    pcntl_waitpid($pid, $status);
    DB::reconnect();
    $leases->release($owner);

    expect(file_get_contents($result))->toBe('blocked');
    unlink($result);
});

it('limits concurrent provider slots to the configured credential capacity', function () {
    $leases = app(TranslationLeaseService::class);
    $credentialId = random_int(100000, 999999);
    $result = tempnam(sys_get_temp_dir(), 'weblex-slots-');
    $children = [];
    DB::disconnect();

    foreach (range(1, config('translation.credential_concurrency', 4) + 1) as $index) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            Cache::clearResolvedInstances();
            $lease = app(TranslationLeaseService::class)->acquireCredentialSlot($credentialId);
            file_put_contents($result.'.'.$index, $lease === null ? '0' : '1');
            usleep(150000);
            app(TranslationLeaseService::class)->release($lease);
            exit(0);
        }
        $children[] = $pid;
    }
    foreach ($children as $pid) {
        pcntl_waitpid($pid, $status);
    }
    DB::reconnect();

    $acquired = collect(range(1, config('translation.credential_concurrency', 4) + 1))
        ->sum(fn (int $index): int => (int) file_get_contents($result.'.'.$index));
    expect($acquired)->toBe(config('translation.credential_concurrency', 4));
    foreach (range(1, config('translation.credential_concurrency', 4) + 1) as $index) {
        unlink($result.'.'.$index);
    }
    unlink($result);
});

it('does not let an expired lease owner release a replacement owner lock', function () {
    config()->set('translation.lease', 1);
    $leases = app(TranslationLeaseService::class);
    $key = 'ownership:'.uniqid();
    $expiredOwner = $leases->acquire($key);
    expect($expiredOwner)->not->toBeNull();
    usleep(1200000);
    $replacement = $leases->acquire($key);
    expect($replacement)->not->toBeNull();

    $leases->release($expiredOwner);
    $third = $leases->acquire($key);
    $leases->release($replacement);

    expect($third)->toBeNull();
});
