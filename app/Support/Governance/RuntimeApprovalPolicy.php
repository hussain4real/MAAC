<?php

namespace App\Support\Governance;

use App\Models\AgentRun;
use App\Models\GovernanceSetting;
use App\Models\Team;

/**
 * Decides whether a run needs human-in-the-loop approval before it may execute.
 * A run is gated when its agent is individually flagged for runtime approval, or
 * when the team has set a sensitivity threshold and the run's data sensitivity is
 * at or above it. The runtime pauses gated runs at `requires_approval`.
 */
class RuntimeApprovalPolicy
{
    /**
     * Determine whether the given run requires approval before executing.
     */
    public function requires(AgentRun $run, Team $team): bool
    {
        if ($run->agent->requires_runtime_approval) {
            return true;
        }

        $threshold = GovernanceSetting::forTeam($team)->runtimeApprovalSensitivity($run->environment);

        return $threshold !== null && $run->sensitivity->isAtLeast($threshold);
    }

    /**
     * A human-readable reason a run was gated, for the run trace.
     */
    public function reason(AgentRun $run): string
    {
        return $run->agent->requires_runtime_approval
            ? 'The agent requires human approval before each run executes.'
            : "Run sensitivity ({$run->sensitivity->label()}) meets the team approval threshold.";
    }
}
