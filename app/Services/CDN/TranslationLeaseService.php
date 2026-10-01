<?php

namespace App\Services\CDN;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

class TranslationLeaseService
{
    public function acquire(string $key): ?Lock
    {
        $lock = Cache::store(config('cache.default'))->lock(
            'translation:lease:'.$key,
            config('translation.lease', 120),
        );

        return $lock->get() ? $lock : null;
    }

    public function release(?Lock $lock): void
    {
        if ($lock !== null) {
            try {
                $lock->release();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /** @param array<int, string> $keys @return array<int, Lock>|null */
    public function acquireMany(array $keys): ?array
    {
        sort($keys, SORT_STRING);
        $locks = [];
        try {
            foreach (array_unique($keys) as $key) {
                $lock = $this->acquire($key);
                if ($lock === null) {
                    $this->releaseMany($locks);

                    return null;
                }
                $locks[] = $lock;
            }
        } catch (\Throwable $exception) {
            $this->releaseMany($locks);
            throw $exception;
        }

        return $locks;
    }

    /** @param array<int, Lock> $locks */
    public function releaseMany(array $locks): void
    {
        foreach (array_reverse($locks) as $lock) {
            $this->release($lock);
        }
    }

    public function acquireCredentialSlot(int $credentialId): ?Lock
    {
        $store = Cache::store(config('cache.default'));
        for ($slot = 0; $slot < config('translation.credential_concurrency', 4); $slot++) {
            $lock = $store->lock("translation:credential:{$credentialId}:{$slot}", config('translation.lease', 120));
            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
