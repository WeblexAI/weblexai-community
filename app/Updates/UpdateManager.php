<?php

namespace App\Updates;

use App\Updates\Contracts\UpdateDriver;
use App\Updates\Drivers\DockerUpdateDriver;
use App\Updates\Drivers\TraditionalUpdateDriver;
use RuntimeException;

class UpdateManager
{
    public function __construct(
        private readonly UpdateLock $lock,
        private readonly DockerUpdateDriver $docker,
        private readonly TraditionalUpdateDriver $traditional,
    ) {}

    public function driver(): UpdateDriver
    {
        return match (config('community.update_driver')) {
            'docker' => $this->docker,
            'traditional' => $this->traditional,
            default => throw new RuntimeException('Automatic updates are disabled.'),
        };
    }

    public function apply(ReleaseManifest $release): void
    {
        $this->lock->exclusively(fn () => $this->driver()->apply($release));
    }
}
