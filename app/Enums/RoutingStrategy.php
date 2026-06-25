<?php

namespace App\Enums;

use App\Models\ModelRoutingPolicy;

/**
 * How a {@see ModelRoutingPolicy} orders the eligible models when selecting one
 * for a run. Every strategy first filters candidates by environment availability,
 * sensitivity clearance, cost ceiling, and provider health; the strategy then
 * decides which surviving candidate is preferred.
 */
enum RoutingStrategy: string
{
    case CostOptimized = 'cost';
    case LatencyOptimized = 'latency';
    case Balanced = 'balanced';

    /**
     * Get the display label for the strategy (e.g. "Cost Optimized").
     */
    public function label(): string
    {
        return match ($this) {
            self::CostOptimized => 'Cost Optimized',
            self::LatencyOptimized => 'Latency Optimized',
            self::Balanced => 'Balanced',
        };
    }

    /**
     * Get all strategies as value/label option pairs.
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
}
