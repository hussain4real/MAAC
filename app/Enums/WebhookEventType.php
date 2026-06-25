<?php

namespace App\Enums;

/**
 * The run lifecycle events MAAC delivers to a registered webhook endpoint. They
 * mirror the externally-observable run transitions: the run starts processing,
 * pauses for a client-side tool, completes, fails, expires, or is cancelled.
 */
enum WebhookEventType: string
{
    case RunRunning = 'run.running';
    case RunToolRequested = 'run.tool_requested';
    case RunRequiresApproval = 'run.requires_approval';
    case RunCompleted = 'run.completed';
    case RunFailed = 'run.failed';
    case RunExpired = 'run.expired';
    case RunCancelled = 'run.cancelled';

    /**
     * Get the human-readable label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RunRunning => 'Run started',
            self::RunToolRequested => 'Tool requested',
            self::RunRequiresApproval => 'Run requires approval',
            self::RunCompleted => 'Run completed',
            self::RunFailed => 'Run failed',
            self::RunExpired => 'Run expired',
            self::RunCancelled => 'Run cancelled',
        };
    }

    /**
     * Get all event types as value/label option pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }

    /**
     * Get all event type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
