<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Release status</x-slot>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-gray-500">Current version</dt>
                <dd class="font-semibold">{{ config('community.version') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Update driver</dt>
                <dd class="font-semibold">{{ config('community.update_driver') }}</dd>
            </div>
            @if ($release)
                <div>
                    <dt class="text-sm text-gray-500">Latest stable version</dt>
                    <dd class="font-semibold">{{ $release['version'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Published</dt>
                    <dd class="font-semibold">{{ $release['published_at'] }}</dd>
                </div>
            @endif
        </dl>

        @if ($error)
            <p class="mt-4 text-sm text-danger-600">{{ $error }}</p>
        @elseif (! $release)
            <p class="mt-4 text-sm text-gray-500">No release feed is configured.</p>
        @elseif (! $release['available'])
            <p class="mt-4 text-sm text-success-600">This installation is up to date.</p>
        @elseif (! $release['compatible'])
            <p class="mt-4 text-sm text-danger-600">The latest release is not compatible with this host.</p>
        @else
            <p class="mt-4 text-sm">
                {{ $release['security'] ? 'Security update available.' : 'Update available.' }}
                <a class="text-primary-600 underline" href="{{ $release['notes_url'] }}" target="_blank" rel="noopener">Release notes</a>
            </p>
        @endif

        <div class="mt-6 flex gap-3">
            <x-filament::button wire:click="check" color="gray">Check now</x-filament::button>
            @if ($release && $release['available'] && $release['compatible'] && config('community.update_driver') !== 'disabled')
                <x-filament::button wire:click="apply" wire:confirm="Back up the installation and apply this update?">
                    Update
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
