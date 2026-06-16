<?php

namespace App\Http\Controllers\Maac;

use App\Enums\ImplStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maac\StoreToolContractRequest;
use App\Http\Requests\Maac\UpdateToolContractRequest;
use App\Models\ToolContract;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ToolContractController extends Controller
{
    /**
     * Create a new tool contract.
     */
    public function store(StoreToolContractRequest $request): RedirectResponse
    {
        Gate::authorize('create', ToolContract::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();
        $implementationStatus = $request->string('execution_mode')->value() === 'client'
            ? ImplStatus::Required->value
            : ImplStatus::Ready->value;

        ToolContract::create([
            ...$validated,
            'team_id' => $team->id,
            'slug' => Slug::unique('tool_contracts', $request->string('name')->value()),
            'status' => 'Active',
            'implementation_status' => $implementationStatus,
            'version' => $validated['version'] ?? '1.0.0',
            'requires_approval' => $validated['requires_approval'] ?? false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tool contract created.']);

        return back();
    }

    /**
     * Update the given tool contract.
     */
    public function update(UpdateToolContractRequest $request, string $currentTeam, ToolContract $tool): RedirectResponse
    {
        Gate::authorize('update', $tool);

        $tool->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tool contract updated.']);

        return back();
    }

    /**
     * Delete (soft delete) the given tool contract.
     */
    public function destroy(Request $request, string $currentTeam, ToolContract $tool): RedirectResponse
    {
        Gate::authorize('delete', $tool);

        $tool->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tool contract deleted.']);

        return back();
    }
}
