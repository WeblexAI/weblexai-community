<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Start with a clean installation</x-slot>
        <x-slot name="description">
            Reset the application when this instance needs to be configured again from the beginning.
        </x-slot>

        <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
            <p class="font-medium text-danger-600 dark:text-danger-400">
                Create and verify a backup before continuing. This action cannot be undone.
            </p>

            <p>The reset permanently removes:</p>

            <ul class="list-disc space-y-2 pl-5">
                <li>Administrators, users, projects, translations, provider credentials, and settings</li>
                <li>Activity history and other application database records</li>
                <li>Files stored on the local public disk</li>
                <li>Application cache, compiled views, and active sessions</li>
            </ul>

            <p>
                Database credentials, Redis credentials, application logs, backups, and objects stored in external
                storage are preserved. After the reset, the installation wizard opens so the instance can be
                configured and a new administrator can be created.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
