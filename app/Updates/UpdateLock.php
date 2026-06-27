<?php

namespace App\Updates;

use RuntimeException;

class UpdateLock
{
    public function exclusively(callable $operation): mixed
    {
        $path = storage_path('app/update.lock');
        $handle = fopen($path, 'c+');

        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another update is already running.');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
