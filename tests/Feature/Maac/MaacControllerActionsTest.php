<?php

use App\Models\Agent;
use App\Models\Application;
use App\Models\LlmProvider;
use App\Models\Project;
use App\Models\ToolContract;

test('a platform admin can update an agent and re-sync its tools', function () {
    [$owner, $team] = ownerAndTeam();
    $application = Application::factory()->for($team)->create();
    $project = Project::factory()->for($application)->create();
    $llm = LlmProvider::factory()->for($team)->create();
    $agent = Agent::factory()->for($project)->for($llm, 'llmProvider')->create();
    $tool = ToolContract::factory()->for($team)->create();

    $this->actingAs($owner)
        ->put(route('agents.update', ['current_team' => $team->slug, 'agent' => $agent->slug]), [
            'name' => 'Renamed Agent',
            'tool_ids' => [$tool->id],
        ])
        ->assertRedirect();

    $agent->refresh();
    expect($agent->name)->toBe('Renamed Agent')
        ->and($agent->tools()->pluck('tool_contracts.id')->all())->toBe([$tool->id]);
});

test('a platform admin can delete an agent', function () {
    [$owner, $team] = ownerAndTeam();
    $application = Application::factory()->for($team)->create();
    $project = Project::factory()->for($application)->create();
    $llm = LlmProvider::factory()->for($team)->create();
    $agent = Agent::factory()->for($project)->for($llm, 'llmProvider')->create();

    $this->actingAs($owner)
        ->delete(route('agents.destroy', ['current_team' => $team->slug, 'agent' => $agent->slug]))
        ->assertRedirect();

    $this->assertSoftDeleted('agents', ['id' => $agent->id]);
});

test('a platform admin can remove a model from the catalog', function () {
    [$owner, $team] = ownerAndTeam();
    $llm = LlmProvider::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('llm-providers.destroy', ['current_team' => $team->slug, 'llmProvider' => $llm->slug]))
        ->assertRedirect();

    $this->assertDatabaseMissing('llm_providers', ['id' => $llm->id]);
});

test('a platform admin can delete a tool contract', function () {
    [$owner, $team] = ownerAndTeam();
    $tool = ToolContract::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('tools.destroy', ['current_team' => $team->slug, 'tool' => $tool->slug]))
        ->assertRedirect();

    $this->assertSoftDeleted('tool_contracts', ['id' => $tool->id]);
});

test('updating a project re-syncs its approved models', function () {
    [$owner, $team] = ownerAndTeam();
    $application = Application::factory()->for($team)->create();
    $project = Project::factory()->for($application)->create();
    $llm = LlmProvider::factory()->for($team)->create();

    $this->actingAs($owner)
        ->put(route('projects.update', ['current_team' => $team->slug, 'project' => $project->slug]), [
            'name' => 'Synced Project',
            'llm_provider_ids' => [$llm->id],
        ])
        ->assertRedirect();

    expect($project->fresh()->llmProviders()->pluck('llm_providers.id')->all())->toBe([$llm->id]);
});
