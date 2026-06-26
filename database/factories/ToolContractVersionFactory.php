<?php

namespace Database\Factories;

use App\Enums\ExecMode;
use App\Models\ToolContract;
use App\Models\ToolContractVersion;
use App\Support\Sdk\ToolCompatibility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ToolContractVersion>
 */
class ToolContractVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ToolContractVersion>
     */
    protected $model = ToolContractVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $input = ['query' => 'string'];
        $output = ['result' => 'string'];

        return [
            'tool_contract_id' => ToolContract::factory(),
            'sequence' => 1,
            'version' => '1.0.0',
            'execution_mode' => ExecMode::Client,
            'schema_fingerprint' => ToolCompatibility::fingerprint($input, $output),
            'input_schema' => $input,
            'output_schema' => $output,
            'config' => null,
            'changed_by' => null,
            'actor_label' => null,
            'notes' => null,
        ];
    }
}
