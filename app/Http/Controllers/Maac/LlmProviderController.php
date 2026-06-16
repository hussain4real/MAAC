<?php

namespace App\Http\Controllers\Maac;

use App\Enums\LlmStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maac\StoreLlmProviderRequest;
use App\Http\Requests\Maac\UpdateLlmProviderRequest;
use App\Models\LlmProvider;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LlmProviderController extends Controller
{
    /**
     * Add a model to the approved LLM catalog.
     */
    public function store(StoreLlmProviderRequest $request): RedirectResponse
    {
        Gate::authorize('create', LlmProvider::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();

        LlmProvider::create([
            ...$validated,
            'team_id' => $team->id,
            'slug' => Slug::unique('llm_providers', $request->string('code')->value()),
            'status' => $validated['status'] ?? LlmStatus::Approved->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model added to the catalog.']);

        return back();
    }

    /**
     * Update the given catalog model.
     */
    public function update(UpdateLlmProviderRequest $request, string $currentTeam, LlmProvider $llmProvider): RedirectResponse
    {
        Gate::authorize('update', $llmProvider);

        $llmProvider->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model updated.']);

        return back();
    }

    /**
     * Remove the given model from the catalog.
     */
    public function destroy(Request $request, string $currentTeam, LlmProvider $llmProvider): RedirectResponse
    {
        Gate::authorize('delete', $llmProvider);

        $llmProvider->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Model removed.']);

        return back();
    }
}
