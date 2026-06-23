<?php

namespace Database\Factories;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEventType;
use App\Models\AgentRun;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WebhookDelivery>
     */
    protected $model = WebhookDelivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'webhook_endpoint_id' => WebhookEndpoint::factory(),
            'agent_run_id' => AgentRun::factory(),
            'event' => WebhookEventType::RunCompleted,
            'payload' => ['event' => WebhookEventType::RunCompleted->value, 'run' => ['status' => 'completed']],
            'signature' => null,
            'status' => WebhookDeliveryStatus::Pending,
            'attempts' => 0,
        ];
    }

    /**
     * Indicate that the delivery succeeded.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WebhookDeliveryStatus::Delivered,
            'attempts' => 1,
            'response_status' => 200,
            'delivered_at' => now(),
            'last_attempted_at' => now(),
        ]);
    }

    /**
     * Indicate that the delivery failed after exhausting its attempts.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WebhookDeliveryStatus::Failed,
            'attempts' => 5,
            'response_status' => 500,
            'error' => 'Endpoint returned HTTP 500.',
            'last_attempted_at' => now(),
        ]);
    }
}
