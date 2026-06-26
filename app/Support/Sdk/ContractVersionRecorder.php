<?php

namespace App\Support\Sdk;

use App\Models\ToolContract;
use App\Models\ToolContractVersion;

/**
 * Maintains a tool contract's append-only version journey. The initial version
 * is snapshotted when the contract is created; thereafter every *material* edit
 * (a change to the versioned configuration — schemas, execution config, limits,
 * sensitivity, approval, redaction, or the version string itself) mints a new
 * snapshot and advances the version. Cosmetic edits (name/description) do not.
 *
 * The version number auto-advances by a patch bump on each material save, unless
 * the editor supplied a strictly higher version explicitly (which then wins).
 */
class ContractVersionRecorder
{
    public function __construct(private readonly JourneyAuditRecorder $audit) {}

    /**
     * The contract fields whose change constitutes a new version. The version
     * string is handled separately because it is both an input and the output.
     *
     * @var list<string>
     */
    private const MATERIAL_FIELDS = [
        'input_schema',
        'output_schema',
        'execution_mode',
        'sensitivity',
        'requires_approval',
        'timeout_seconds',
        'max_payload_kb',
        'redaction',
        'http_config',
        'mcp_connector_id',
        'mcp_tool_name',
        'knowledge_source_id',
        'knowledge_config',
    ];

    /**
     * Snapshot the contract's initial version (called right after creation).
     */
    public function recordInitial(ToolContract $contract): ToolContractVersion
    {
        return $this->snapshot($contract);
    }

    /**
     * Apply a normalized update to the contract, minting a new version when the
     * change is material. Returns the new snapshot, or null for a cosmetic-only
     * (or empty) change.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function applyUpdate(ToolContract $contract, array $attributes): ?ToolContractVersion
    {
        $latest = $this->latestVersion($contract) ?? $contract->version;

        $contract->fill($attributes);

        if (! $contract->isDirty(self::MATERIAL_FIELDS) && ! $contract->isDirty('version')) {
            $contract->save();

            return null;
        }

        $contract->version = $this->resolveVersion($contract->version, $latest);
        $contract->save();

        $snapshot = $this->snapshot($contract);
        $this->audit->contractVersioned($contract, $latest, $snapshot);

        return $snapshot;
    }

    /**
     * Resolve the version to persist: the editor's value when it is strictly
     * higher than the latest recorded version, otherwise a patch bump of it.
     */
    private function resolveVersion(string $requested, string $latest): string
    {
        return version_compare($requested, $latest, '>') ? $requested : $this->bumpPatch($latest);
    }

    /**
     * Increment the patch component of a semver-ish version (defaulting absent
     * minor/patch parts to zero); append a numeric suffix to a non-numeric one.
     */
    private function bumpPatch(string $version): string
    {
        if (preg_match('/^(\d+)(?:\.(\d+))?(?:\.(\d+))?$/', $version, $matches) === 1) {
            $major = (int) $matches[1];
            $minor = (int) ($matches[2] ?? 0);
            $patch = (int) ($matches[3] ?? 0);

            return $major.'.'.$minor.'.'.($patch + 1);
        }

        return $version.'.1';
    }

    /**
     * Write a snapshot row capturing the contract's current functional config.
     */
    private function snapshot(ToolContract $contract): ToolContractVersion
    {
        $user = auth()->user();

        return $contract->versions()->create([
            'sequence' => $this->nextSequence($contract),
            'version' => $contract->version,
            'execution_mode' => $contract->execution_mode->value,
            'schema_fingerprint' => $contract->schemaFingerprint(),
            'input_schema' => $contract->input_schema,
            'output_schema' => $contract->output_schema,
            'config' => $this->config($contract),
            'changed_by' => $user?->getAuthIdentifier(),
            'actor_label' => $user?->name,
        ]);
    }

    /**
     * Capture the non-schema functional configuration carried in the snapshot.
     *
     * @return array<string, mixed>
     */
    private function config(ToolContract $contract): array
    {
        return [
            'sensitivity' => $contract->sensitivity->value,
            'requires_approval' => $contract->requires_approval,
            'timeout_seconds' => $contract->timeout_seconds,
            'max_payload_kb' => $contract->max_payload_kb,
            'redaction' => $contract->redaction,
            'http_config' => $contract->http_config,
            'mcp_connector_id' => $contract->mcp_connector_id,
            'mcp_tool_name' => $contract->mcp_tool_name,
            'knowledge_source_id' => $contract->knowledge_source_id,
            'knowledge_config' => $contract->knowledge_config,
        ];
    }

    /**
     * The next monotonic sequence number for the contract's journey.
     */
    private function nextSequence(ToolContract $contract): int
    {
        return (int) ToolContractVersion::query()
            ->where('tool_contract_id', $contract->id)
            ->max('sequence') + 1;
    }

    /**
     * The latest recorded version string for the contract (null if none yet).
     */
    private function latestVersion(ToolContract $contract): ?string
    {
        $version = ToolContractVersion::query()
            ->where('tool_contract_id', $contract->id)
            ->orderByDesc('sequence')
            ->value('version');

        return $version === null ? null : (string) $version;
    }
}
