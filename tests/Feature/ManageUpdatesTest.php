<?php

use App\Filament\Pages\ManageUpdates;
use Livewire\Livewire;

use function Filament\Notifications\Testing\assertNotified;

it('has a default release feed configuration', function () {
    expect(config('community.release_feed_url'))
        ->toBe('https://github.com/weblexai/weblexai-community/releases/latest/download/stable.json')
        ->and(config('community.release_public_key'))
        ->toBe('zmQC1sHMkYYb01WwmEzFpbIYK/hCSra2hQBw+eVWr9M=');
});

it('notifies the administrator when no release feed is configured', function () {
    config(['community.release_feed_url' => null]);

    Livewire::test(ManageUpdates::class)
        ->call('check');

    assertNotified('No release feed configured');
});
