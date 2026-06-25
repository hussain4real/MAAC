<?php

namespace Database\Factories;

use App\Enums\VaultSecretKind;
use App\Models\Team;
use App\Models\VaultSecret;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VaultSecret>
 */
class VaultSecretFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<VaultSecret>
     */
    protected $model = VaultSecret::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plain = 'sk-'.Str::random(40);

        return [
            'team_id' => Team::factory(),
            'reference' => VaultSecretKind::Generic->reference(fake()->unique()->slug(2)),
            'name' => fake()->unique()->words(2, true),
            'kind' => VaultSecretKind::Generic,
            'ciphertext' => $plain,
            'last_four' => substr($plain, -4),
            'version' => 1,
            'rotated_at' => null,
            'last_accessed_at' => null,
            'accessed_count' => 0,
            'created_by' => null,
        ];
    }

    /**
     * Indicate the secret holds an LLM provider API key.
     */
    public function llmKey(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => VaultSecretKind::LlmKey,
            'reference' => VaultSecretKind::LlmKey->reference(fake()->unique()->slug(2)),
        ]);
    }

    /**
     * Set the plaintext value the secret holds.
     */
    public function withValue(string $plaintext): static
    {
        return $this->state(fn (array $attributes): array => [
            'ciphertext' => $plaintext,
            'last_four' => substr($plaintext, -4),
        ]);
    }
}
