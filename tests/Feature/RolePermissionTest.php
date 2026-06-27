<?php

use App\Enums\CollaboratorRole;
use App\Models\Language;
use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $owner = User::factory()->create();
    $language = Language::query()->create([
        'name' => 'English',
        'country_name' => 'United Kingdom',
        'iso_2' => 'en',
        'iso_3' => 'eng',
        'color' => '#3366ff',
    ]);

    $this->owner = $owner;
    $this->project = Project::query()->create([
        'user_id' => $owner->id,
        'name' => 'Test Project',
        'original_language_id' => $language->id,
    ]);
    $this->policy = app(ProjectPolicy::class);
});

function projectMember(Project $project, CollaboratorRole $role): User
{
    $user = User::factory()->create();
    $project->collaborators()->attach($user, ['role' => $role->value]);

    return $user;
}

test('owner can manage settings and content', function () {
    expect($this->policy->view($this->owner, $this->project))->toBeTrue()
        ->and($this->policy->update($this->owner, $this->project))->toBeTrue()
        ->and(CollaboratorRole::isAuthorized(
            $this->project,
            $this->owner,
            CollaboratorRole::canManageContent(),
        ))->toBeTrue();
});

test('manager can manage settings and content', function () {
    $manager = projectMember($this->project, CollaboratorRole::MANAGER);

    expect($this->policy->view($manager, $this->project))->toBeTrue()
        ->and($this->policy->update($manager, $this->project))->toBeTrue()
        ->and(CollaboratorRole::isAuthorized(
            $this->project,
            $manager,
            CollaboratorRole::canManageContent(),
        ))->toBeTrue()
        ->and($this->policy->manageCollaborators($manager, $this->project))->toBeFalse();
});

test('translator can manage content but not settings or members', function () {
    $translator = projectMember($this->project, CollaboratorRole::TRANSLATOR);

    expect($this->policy->view($translator, $this->project))->toBeTrue()
        ->and($this->policy->update($translator, $this->project))->toBeFalse()
        ->and(CollaboratorRole::isAuthorized(
            $this->project,
            $translator,
            CollaboratorRole::canManageContent(),
        ))->toBeTrue()
        ->and($this->policy->manageCollaborators($translator, $this->project))->toBeFalse();
});

test('viewer has read-only access', function () {
    $viewer = projectMember($this->project, CollaboratorRole::VIEWER);

    expect($this->policy->view($viewer, $this->project))->toBeTrue()
        ->and($this->policy->update($viewer, $this->project))->toBeFalse()
        ->and(CollaboratorRole::isAuthorized(
            $this->project,
            $viewer,
            CollaboratorRole::canManageContent(),
        ))->toBeFalse()
        ->and($this->policy->manageCollaborators($viewer, $this->project))->toBeFalse();
});

test('unassigned user cannot access a project', function () {
    $user = User::factory()->create();

    expect($this->policy->view($user, $this->project))->toBeFalse();
});
