<?php

namespace App\Support\Filament;

use Swis\Filament\Backgrounds\Contracts\ProvidesImages;
use Swis\Filament\Backgrounds\Image;

class WeblexLoginBackground implements ProvidesImages
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getImage(): Image
    {
        return new Image(
            'linear-gradient(90deg, rgba(3, 7, 18, 0.62), rgba(3, 7, 18, 0.2)), url("/images/swisnl/filament-backgrounds/curated-by-swis/03.jpg")',
            null
        );
    }
}
