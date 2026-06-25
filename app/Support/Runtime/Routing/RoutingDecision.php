<?php

namespace App\Support\Runtime\Routing;

use App\Enums\RoutingStrategy;
use App\Models\LlmProvider;

/**
 * The outcome of routing a run to a model: the chosen provider (null when no
 * candidate is eligible), the ordered eligible chain used for fail-over, every
 * candidate's evaluation, and a rationale. Carries enough detail to record the
 * decision on the run trace for later audit.
 */
final readonly class RoutingDecision
{
    /**
     * @param  array<int, LlmProvider>  $eligible
     * @param  array<int, CandidateEvaluation>  $considered
     */
    public function __construct(
        public ?LlmProvider $provider,
        public array $eligible,
        public array $considered,
        public ?RoutingStrategy $strategy,
        public string $rationale,
    ) {}

    /**
     * The ordered eligible model ids, used as the run's fail-over chain.
     *
     * @return array<int, string>
     */
    public function chainIds(): array
    {
        return array_map(fn (LlmProvider $provider): string => $provider->id, $this->eligible);
    }

    /**
     * Serialize the decision for the run trace.
     *
     * @return array<string, mixed>
     */
    public function traceData(): array
    {
        return [
            'model' => $this->provider?->code,
            'strategy' => $this->strategy?->value,
            'rationale' => $this->rationale,
            'considered' => array_map(fn (CandidateEvaluation $candidate): array => $candidate->toArray(), $this->considered),
        ];
    }
}
