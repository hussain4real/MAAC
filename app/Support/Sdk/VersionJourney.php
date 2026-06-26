<?php

namespace App\Support\Sdk;

use App\Enums\ExecMode;
use App\Enums\ImplStatus;
use App\Models\Application;
use App\Models\Team;
use App\Models\ToolContract;
use App\Models\ToolContractVersion;
use App\Models\ToolImplementation;
use App\Models\ToolImplementationEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read model for the tool-contract version journey. It assembles, from the two
 * append-only histories (`tool_contract_versions` and
 * `tool_implementation_events`) plus the current implementation rows, the
 * per-tool and per-application timelines surfaced in the console, API, and
 * export. Actor display uses the denormalized `actor_label` captured at write
 * time, so a since-removed user still shows in the history.
 */
class VersionJourney
{
    /**
     * Default cap on timeline events returned per entity (the console view);
     * pass null for the full history (API/export).
     */
    public const DEFAULT_EVENT_LIMIT = 100;

    /**
     * Build the full journey for a team: every client-side tool's journey plus a
     * per-application rollup of their implementation timelines.
     *
     * @return array{tools: array<int, array<string, mixed>>, applications: array<int, array<string, mixed>>, truncated: bool}
     */
    public function teamReport(Team $team, ?int $eventLimit = self::DEFAULT_EVENT_LIMIT): array
    {
        $tools = ToolContract::query()
            ->where('team_id', $team->id)
            ->where('execution_mode', ExecMode::Client)
            ->with('application')
            ->orderBy('name')
            ->get();

        $applications = Application::query()
            ->where('team_id', $team->id)
            ->whereIn('id', $tools->pluck('application_id')->filter()->unique()->all())
            ->orderBy('name')
            ->get();

        $toolReports = $tools->map(fn (ToolContract $tool): array => $this->toolReport($tool, $eventLimit))->all();
        $appReports = $applications->map(fn (Application $application): array => $this->applicationReport($application, $eventLimit))->all();

        return [
            'tools' => $toolReports,
            'applications' => $appReports,
            'truncated' => $this->anyTruncated($toolReports) || $this->anyTruncated($appReports),
        ];
    }

    /**
     * Build the journey for a single tool: its version snapshots, the current
     * per-application/environment implementation state, and its event timeline.
     *
     * @return array<string, mixed>
     */
    public function toolReport(ToolContract $tool, ?int $eventLimit = self::DEFAULT_EVENT_LIMIT): array
    {
        $tool->loadMissing('application');

        $versions = $tool->versions()->orderByDesc('sequence')->get();

        $implementations = $tool->implementations()
            ->with('application')
            ->get()
            ->sortBy(fn (ToolImplementation $impl): string => $impl->application->name.$impl->environment->value)
            ->values();

        $totalEvents = ToolImplementationEvent::query()->where('tool_contract_id', $tool->id)->count();

        return [
            'id' => $tool->slug,
            'slug' => $tool->slug,
            'name' => $tool->name,
            'execution_mode' => $tool->execution_mode->value,
            'current_version' => $tool->version,
            'owner' => $tool->ownerLabel(),
            'application' => $tool->application_id === null ? null : $tool->application->name,
            'versions' => $versions->map(fn (ToolContractVersion $version): array => $this->versionRow($version, $tool->version))->all(),
            'implementations' => $implementations->map(fn (ToolImplementation $impl): array => $this->implementationRow($impl))->all(),
            'drift_count' => $this->driftCount($implementations),
            'events' => $this->eventRows(ToolImplementationEvent::query()->where('tool_contract_id', $tool->id), $eventLimit),
            'events_truncated' => $eventLimit !== null && $totalEvents > $eventLimit,
        ];
    }

    /**
     * Build the journey for a single application: the current state of each tool
     * handler it has reported and the application's implementation event timeline.
     *
     * @return array<string, mixed>
     */
    public function applicationReport(Application $application, ?int $eventLimit = self::DEFAULT_EVENT_LIMIT): array
    {
        $implementations = ToolImplementation::query()
            ->where('application_id', $application->id)
            ->with('toolContract')
            ->get()
            ->sortBy(fn (ToolImplementation $impl): string => $impl->toolContract->name.$impl->environment->value)
            ->values();

        $totalEvents = ToolImplementationEvent::query()->where('application_id', $application->id)->count();

        return [
            'id' => $application->slug,
            'slug' => $application->slug,
            'name' => $application->name,
            'environment' => $application->environment->value,
            'tools' => $implementations->map(fn (ToolImplementation $impl): array => $this->applicationToolRow($impl))->all(),
            'drift_count' => $this->driftCount($implementations),
            'events' => $this->eventRows(ToolImplementationEvent::query()->where('application_id', $application->id), $eventLimit),
            'events_truncated' => $eventLimit !== null && $totalEvents > $eventLimit,
        ];
    }

    /**
     * Map a contract version snapshot to its display row.
     *
     * @return array<string, mixed>
     */
    private function versionRow(ToolContractVersion $version, string $currentVersion): array
    {
        return [
            'sequence' => $version->sequence,
            'version' => $version->version,
            'execution_mode' => $version->execution_mode->value,
            'schema_fingerprint' => $version->schema_fingerprint,
            'notes' => $version->notes,
            'changed_by' => $version->actor_label,
            'created_at' => $version->created_at?->toIso8601String(),
            'is_current' => $version->version === $currentVersion,
            'input_schema' => $version->input_schema,
            'output_schema' => $version->output_schema,
        ];
    }

    /**
     * Map a current implementation row (per application/environment) for a tool.
     *
     * @return array<string, mixed>
     */
    private function implementationRow(ToolImplementation $impl): array
    {
        return [
            'application' => $impl->application->name,
            'application_slug' => $impl->application->slug,
            'environment' => $impl->environment->value,
            'status' => $impl->status->value,
            'status_label' => $impl->status->label(),
            'implemented_version' => $impl->implemented_version,
            'schema_fingerprint' => $impl->schema_fingerprint,
            'handler_name' => $impl->handler_name,
            'last_validated_at' => $impl->last_validated_at?->toIso8601String(),
        ];
    }

    /**
     * Map a current implementation row (per tool/environment) for an application.
     *
     * @return array<string, mixed>
     */
    private function applicationToolRow(ToolImplementation $impl): array
    {
        return [
            'tool' => $impl->toolContract->name,
            'tool_slug' => $impl->toolContract->slug,
            'environment' => $impl->environment->value,
            'status' => $impl->status->value,
            'status_label' => $impl->status->label(),
            'implemented_version' => $impl->implemented_version,
            'contract_version' => $impl->toolContract->version,
            'schema_fingerprint' => $impl->schema_fingerprint,
            'last_validated_at' => $impl->last_validated_at?->toIso8601String(),
        ];
    }

    /**
     * Load and map a bounded slice of an event query (newest first).
     *
     * @param  Builder<ToolImplementationEvent>  $query
     * @return array<int, array<string, mixed>>
     */
    private function eventRows(Builder $query, ?int $limit): array
    {
        $query->with(['application', 'toolContract'])->orderByDesc('created_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (ToolImplementationEvent $event): array => $this->eventRow($event))->all();
    }

    /**
     * Map an implementation timeline event to its display row.
     *
     * @return array<string, mixed>
     */
    private function eventRow(ToolImplementationEvent $event): array
    {
        return [
            'id' => $event->id,
            'tool' => $event->toolContract->name,
            'tool_slug' => $event->toolContract->slug,
            'application' => $event->application->name,
            'application_slug' => $event->application->slug,
            'environment' => $event->environment->value,
            'status' => $event->status->value,
            'status_label' => $event->status->label(),
            'previous_status' => $event->previous_status?->value,
            'previous_status_label' => $event->previous_status?->label(),
            'reason' => $event->reason->value,
            'reason_label' => $event->reason->label(),
            'reported_version' => $event->reported_version,
            'contract_version' => $event->contract_version,
            'actor' => $event->actor_label,
            'created_at' => $event->created_at?->toIso8601String(),
        ];
    }

    /**
     * Count the implementations currently in a drifted state.
     *
     * @param  Collection<int, ToolImplementation>  $implementations
     */
    private function driftCount($implementations): int
    {
        return $implementations
            ->filter(fn (ToolImplementation $impl): bool => in_array($impl->status, [ImplStatus::Outdated, ImplStatus::Incompatible], true))
            ->count();
    }

    /**
     * Whether any of the given reports had its event list truncated by the limit.
     *
     * @param  array<int, array<string, mixed>>  $reports
     */
    private function anyTruncated(array $reports): bool
    {
        foreach ($reports as $report) {
            if ($report['events_truncated'] === true) {
                return true;
            }
        }

        return false;
    }
}
