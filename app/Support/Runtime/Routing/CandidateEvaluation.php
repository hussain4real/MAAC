<?php

namespace App\Support\Runtime\Routing;

use App\Models\LlmProvider;

/**
 * The router's verdict on one candidate model: whether it is eligible for the
 * run, a human-readable reason when it is not, and the cost/latency/health
 * signals that ordered it. Recorded on the run trace so a routing decision can be
 * audited after the fact.
 */
final readonly class CandidateEvaluation
{
    public function __construct(
        public LlmProvider $provider,
        public bool $eligible,
        public string $reason,
        public float $totalCost,
        public ?int $avgLatencyMs,
        public bool $healthy,
    ) {}

    /**
     * The combined per-1k cost used to order cost-optimized routing.
     */
    public static function costOf(LlmProvider $provider): float
    {
        return round($provider->input_cost + $provider->output_cost, 4);
    }

    /**
     * Serialize the evaluation for the run trace.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'model' => $this->provider->code,
            'eligible' => $this->eligible,
            'reason' => $this->reason,
            'cost_per_1k' => $this->totalCost,
            'avg_latency_ms' => $this->avgLatencyMs,
            'healthy' => $this->healthy,
        ];
    }
}
