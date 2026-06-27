<?php

namespace App\Updates\Contracts;

use App\Updates\ReleaseManifest;

interface UpdateDriver
{
    public function name(): string;

    public function apply(ReleaseManifest $release): void;
}
