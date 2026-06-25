<?php

namespace Database\Factories;

use App\Enums\IncidentActionType;
use App\Models\IncidentAction;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentAction>
 */
class IncidentActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<IncidentAction>
     */
    protected $model = IncidentAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'actor_user_id' => null,
            'actor_label' => fake()->name(),
            'type' => IncidentActionType::DisableModel,
            'subject_type' => null,
            'subject_id' => null,
            'subject_label' => fake()->words(2, true),
            'reason' => fake()->sentence(),
            'environment' => null,
            'reverted_at' => null,
            'reverted_by' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate an application-freeze incident.
     */
    public function freeze(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => IncidentActionType::FreezeApplication,
        ]);
    }
}
