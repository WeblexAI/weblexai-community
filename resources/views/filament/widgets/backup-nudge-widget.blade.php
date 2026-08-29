<x-filament::callout
    color="danger"
    icon="heroicon-m-shield-exclamation"
    heading="No backups exist yet"
    description="Your project data, provider credentials, and settings are not protected. Create the first backup now."
>
    <x-slot name="controls">
        <x-filament::button
            :href="$backupsUrl"
            color="danger"
            size="sm"
            icon="heroicon-m-arrow-down-tray"
        >
            Create a backup
        </x-filament::button>
    </x-slot>
</x-filament::callout>
