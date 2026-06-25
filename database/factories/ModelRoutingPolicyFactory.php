<?php

namespace Database\Factories;

use App\Enums\RoutingStrategy;
use App\Models\Agent;
use App\Models\ModelRoutingPolicy;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModelRoutingPolicy>
 */
class ModelRoutingPolicyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ModelRoutingPolicy>
     */
    protected $model = ModelRoutingPolicy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'agent_id' => Agent::factory(),
            'name' => fake()->unique()->company().' routing',
            'strategy' => RoutingStrategy::Balanced,
            'primary_provider_id' => null,
            'fallback_provider_ids' => [],
            'max_cost_per_1k' => null,
            'max_latency_ms' => null,
            'enabled' => true,
            'created_by' => null,
        ];
    }

    /**
     * Indicate the policy is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'enabled' => false,
        ]);
    }

    /**
     * Use the cost-optimized strategy.
     */
    public function costOptimized(): static
    {
        return $this->state(fn (array $attributes): array => [
            'strategy' => RoutingStrategy::CostOptimized,
        ]);
    }
}
