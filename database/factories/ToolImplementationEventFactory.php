<?php

namespace Database\Factories;

use App\Enums\Environment;
use App\Enums\ImplementationEventReason;
use App\Enums\ImplStatus;
use App\Models\Application;
use App\Models\ToolContract;
use App\Models\ToolImplementationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ToolImplementationEvent>
 */
class ToolImplementationEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ToolImplementationEvent>
     */
    protected $model = ToolImplementationEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tool_contract_id' => ToolContract::factory(),
            'application_id' => Application::factory(),
            'tool_implementation_id' => null,
            'tool_contract_version_id' => null,
            'environment' => Environment::Production,
            'status' => ImplStatus::Implemented,
            'previous_status' => null,
            'reason' => ImplementationEventReason::Reported,
            'reported_version' => '1.0.0',
            'schema_fingerprint' => null,
            'contract_version' => '1.0.0',
            'actor_user_id' => null,
            'actor_label' => null,
        ];
    }
}
