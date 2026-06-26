<?php

namespace App\Support\Sdk;

use App\Enums\ExecMode;
use App\Enums\ImplementationEventReason;
use App\Enums\ImplStatus;
use App\Models\ToolContract;
use App\Support\Webhooks\ImplementationWebhookEmitter;
use Illuminate\Support\Facades\Date;

/**
 * Re-reconciles an application's reported client-side handlers after its tool
 * contract changes. A contract edit can leave a previously-current handler
 * behind, so each implementation is re-evaluated against the new contract —
 * by both reported version and reported schema fingerprint, so a schema-only
 * edit (no version bump) is caught as drift. An implementation that transitions
 * has its status refreshed, the transition appended to its timeline, and a
 * webhook fired: `implementation.outdated` when a current handler drifts, or
 * `implementation.recovered` when a drifted handler comes back.
 */
class ToolImplementationReconciler
{
    public function __construct(
        private readonly ImplementationWebhookEmitter $webhooks,
        private readonly ImplementationEventRecorder $events,
        private readonly JourneyAuditRecorder $audit,
    ) {}

    /**
     * Re-evaluate every implementation of the contract and notify on transitions.
     */
    public function reconcile(ToolContract $contract): void
    {
        if ($contract->execution_mode !== ExecMode::Client) {
            return;
        }

        $contract->loadMissing('implementations.application');

        foreach ($contract->implementations as $implementation) {
            $status = ToolCompatibility::evaluate($contract, (string) $implementation->implemented_version, $implementation->schema_fingerprint);

            if ($status === $implementation->status) {
                continue;
            }

            $previousStatus = $implementation->status;
            $implementation->update(['status' => $status->value, 'last_validated_at' => Date::now()]);

            $this->events->record($contract, $implementation, $previousStatus, ImplementationEventReason::ContractChanged);

            if ($previousStatus === ImplStatus::Implemented && $status !== ImplStatus::Implemented) {
                $this->audit->implementationTransition($contract, $implementation, $previousStatus, 'tool_implementation.outdated');
                $this->webhooks->emitOutdated($implementation->application, $implementation->environment, $contract, $implementation);
            } elseif ($this->events->isRecovery($previousStatus, $status)) {
                $this->audit->implementationTransition($contract, $implementation, $previousStatus, 'tool_implementation.recovered');
                $this->webhooks->emitRecovered($implementation->application, $implementation->environment, $contract, $implementation);
            }
        }
    }
}
