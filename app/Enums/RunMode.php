<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * How an agent run is invoked. A `sync` run is driven inline and the runtime API
 * blocks until it reaches a boundary (completed, paused, or failed). An `async`
 * run is created immediately and driven by a queued worker, so the caller does
 * not hold the request open — it learns the outcome by polling, streaming, or a
 * webhook.
 */
enum RunMode: string
{
    case Sync = 'sync';
    case Async = 'async';

    /**
     * Get the human-readable label for the run mode.
     */
    public function label(): string
    {
        return Str::headline($this->value);
    }

    /**
     * Whether the run is driven by a queued worker rather than inline.
     */
    public function isAsync(): bool
    {
        return $this === self::Async;
    }

    /**
     * Get all modes as value/label option pairs.
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
     * Get all mode values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
