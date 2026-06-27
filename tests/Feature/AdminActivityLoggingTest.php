<?php

use App\Enums\CollaboratorRole;
use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\RelationManagers\AcceptedOriginsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MembersRelationManager;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectAcceptedOrigin;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);
    $this->admin->assignRole(UserRole::ADMIN->value);
    $this->project = Project::query()->create([
        'user_id' => $this->admin->id,
        'name' => 'Documentation',
    ]);

    $this->actingAs($this->admin);
});

it('logs accepted origin lifecycle actions', function () {
    Livewire::test(AcceptedOriginsRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('create', data: [
        'origin' => 'https://example.com',
    ]);

    $origin = ProjectAcceptedOrigin::query()->sole();

    expectAdminActivity(
        'Added accepted origin "https://example.com" to project "Documentation".',
        'created',
    );

    Livewire::test(AcceptedOriginsRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('edit', $origin, [
        'origin' => 'https://www.example.com',
    ]);

    expectAdminActivity(
        'Updated accepted origin "https://www.example.com" for project "Documentation".',
        'updated',
    );

    Livewire::test(AcceptedOriginsRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('delete', $origin->refresh());

    expectAdminActivity(
        'Removed accepted origin "https://www.example.com" from project "Documentation".',
        'deleted',
    );
});

it('logs project member lifecycle actions', function () {
    $user = User::factory()->create(['is_active' => ModelStatus::ACTIVE]);

    Livewire::test(MembersRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('attach', data: [
        'recordId' => $user->id,
        'role' => CollaboratorRole::VIEWER->value,
    ]);

    expectAdminActivity(
        sprintf('Added user "%s" to project "Documentation" as Viewer.', $user->email),
        'created',
    );

    Livewire::test(MembersRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('changeRole', $user, [
        'role' => CollaboratorRole::MANAGER->value,
    ]);

    expectAdminActivity(
        sprintf('Changed user "%s" role in project "Documentation" to Manager.', $user->email),
        'updated',
    );

    Livewire::test(MembersRelationManager::class, [
        'ownerRecord' => $this->project,
        'pageClass' => EditProject::class,
    ])->callTableAction('detach', $user);

    expectAdminActivity(
        sprintf('Removed user "%s" from project "Documentation".', $user->email),
        'deleted',
    );
});

it('logs project key rotation and deletion', function () {
    Livewire::test(EditProject::class, [
        'record' => $this->project->getRouteKey(),
    ])->callAction('rotateApiKey');

    expectAdminActivity(
        'Rotated API key for project "Documentation".',
        'updated',
    );

    Livewire::test(EditProject::class, [
        'record' => $this->project->getRouteKey(),
    ])->callAction('delete');

    expectAdminActivity(
        'Deleted project "Documentation".',
        'deleted',
    );
});

it('logs user deletion', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, [
        'record' => $user->getRouteKey(),
    ])->callAction('delete');

    expectAdminActivity(
        sprintf('Deleted user "%s".', $user->email),
        'deleted',
    );
});

function expectAdminActivity(string $description, string $event): void
{
    $activity = ActivityLog::query()
        ->where('log_name', 'admin')
        ->where('description', $description)
        ->latest('id')
        ->first();

    expect($activity)
        ->not->toBeNull()
        ->and($activity->event)->toBe($event);
}
