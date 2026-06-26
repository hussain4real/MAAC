<?php

namespace App\Support\Sdk;

use App\Enums\ImplementationEventReason;
use App\Enums\ImplStatus;
use App\Models\ToolContract;
use App\Models\ToolContractVersion;
use App\Models\ToolImplementation;
use App\Models\ToolImplementationEvent;

/**
 * Appends entries to a client-side tool's implementation timeline. A row is
 * written on every SDK report and on every contract-change reconcile transition,
 * capturing the status edge and the contract version it was evaluated against so
 * the consumer's journey is queryable over time.
 */
class ImplementationEventRecorder
{
    /**
     * Record a timeline event for the implementation's current status, carrying
     * the status it transitioned from (null when this is its first entry).
     */
    public function record(
        ToolContract $contract,
        ToolImplementation $implementation,
        ?ImplStatus $previousStatus,
        ImplementationEventReason $reason,
    ): ToolImplementationEvent {
        $user = auth()->user();

        return ToolImplementationEvent::create([
            'tool_contract_id' => $contract->id,
            'application_id' => $implementation->application_id,
            'tool_implementation_id' => $implementation->id,
            'tool_contract_version_id' => $this->latestVersionId($contract),
            'environment' => $implementation->environment->value,
            'status' => $implementation->status->value,
            'previous_status' => $previousStatus?->value,
            'reason' => $reason->value,
            'reported_version' => $implementation->implemented_version,
            'schema_fingerprint' => $implementation->schema_fingerprint,
            'contract_version' => $contract->version,
            'actor_user_id' => $user?->getAuthIdentifier(),
            'actor_label' => $user?->name,
        ]);
    }

    /**
     * Determine whether a status edge is a recovery: a previously drifted handler
     * (outdated or incompatible) returning to {@see ImplStatus::Implemented}.
     */
    public function isRecovery(?ImplStatus $previousStatus, ImplStatus $status): bool
    {
        return $status === ImplStatus::Implemented
            && in_array($previousStatus, [ImplStatus::Outdated, ImplStatus::Incompatible], true);
    }

    /**
     * The id of the contract's latest version snapshot (null if none yet).
     */
    private function latestVersionId(ToolContract $contract): ?string
    {
        $id = ToolContractVersion::query()
            ->where('tool_contract_id', $contract->id)
            ->orderByDesc('sequence')
            ->value('id');

        return $id === null ? null : (string) $id;
    }
}
