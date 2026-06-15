<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * MAAC console (Phase 1) smoke coverage: every screen in the sidebar
 * resolves for an authenticated, team-scoped user and renders the
 * expected Inertia page component.
 *
 * @return array<int, array{0: string, 1: array<string, string>, 2: string}>
 */
function maacScreens(): array
{
    return [
        ['dashboard', [], 'dashboard'],
        ['applications', [], 'maac/applications/index'],
        ['applications.show', ['application' => 'MOP'], 'maac/applications/show'],
        ['projects', [], 'maac/projects/index'],
        ['agents', [], 'maac/agents/index'],
        ['agents.create', [], 'maac/agents/create'],
        ['agents.show', ['agent' => 'ag_ops_summary'], 'maac/agents/show'],
        ['tools', [], 'maac/tools/index'],
        ['tools.show', ['tool' => 'getOperationalRecords'], 'maac/tools/show'],
        ['sdk', [], 'maac/sdk'],
        ['playground', [], 'maac/playground'],
        ['runs', [], 'maac/runs/index'],
        ['runs.show', ['run' => 'run_8fa31c'], 'maac/runs/show'],
        ['llm-providers', [], 'maac/llm-providers'],
        ['governance', [], 'maac/governance'],
        ['platform-settings', [], 'maac/settings'],
    ];
}

test('authenticated users can reach every MAAC console screen', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    foreach (maacScreens() as [$name, $params, $component]) {
        $this
            ->actingAs($user)
            ->get(route($name, array_merge(['current_team' => $team->slug], $params)))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
});

test('guests are redirected from MAAC console routes to login', function () {
    $this
        ->get(route('applications', ['current_team' => 'any-team']))
        ->assertRedirect(route('login'));
});

test('console detail routes forward the record identifier as a prop', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this
        ->actingAs($user)
        ->get(route('applications.show', ['current_team' => $team->slug, 'application' => 'MOP']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maac/applications/show')
            ->where('id', 'MOP'),
        );

    $this
        ->actingAs($user)
        ->get(route('runs.show', ['current_team' => $team->slug, 'run' => 'run_8fa31c']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('maac/runs/show')
            ->where('id', 'run_8fa31c'),
        );
});
