<?php

namespace App\Support\Runtime\Routing;

/**
 * A point-in-time health reading for one model provider, derived from its recent
 * runs: how many runs were sampled, the model-attributable failure rate, whether
 * that puts the provider over the unhealthy threshold, and the observed average
 * latency. The model router uses this to deprioritize or fail over from a
 * degraded provider.
 */
final readonly class ProviderHealthSnapshot
{
    public function __construct(
        public int $sampleSize,
        public float $failureRate,
        public bool $healthy,
        public ?int $avgLatencyMs,
    ) {}

    /**
     * A neutral, healthy snapshot for a provider with no recent runs.
     */
    public static function unknown(): self
    {
        return new self(0, 0.0, true, null);
    }
}
