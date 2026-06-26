<?php

namespace App\Models;

use App\Enums\ExecMode;
use App\Support\Sdk\ContractVersionRecorder;
use Database\Factories\ToolContractVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only snapshot of a tool contract's functional configuration at a
 * point in time. A new row is minted whenever a material edit changes the
 * contract (see {@see ContractVersionRecorder}), giving each
 * tool a queryable version journey that mirrors {@see AgentVersion}.
 *
 * @property string $id
 * @property string $tool_contract_id
 * @property int $sequence
 * @property string $version
 * @property ExecMode $execution_mode
 * @property string $schema_fingerprint
 * @property array<string, string> $input_schema
 * @property array<string, string> $output_schema
 * @property array<string, mixed>|null $config
 * @property int|null $changed_by
 * @property string|null $actor_label
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ToolContract $toolContract
 * @property-read User|null $changedBy
 */
#[Fillable(['tool_contract_id', 'sequence', 'version', 'execution_mode', 'schema_fingerprint', 'input_schema', 'output_schema', 'config', 'changed_by', 'actor_label', 'notes'])]
class ToolContractVersion extends Model
{
    /** @use HasFactory<ToolContractVersionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the contract this version snapshot belongs to.
     *
     * @return BelongsTo<ToolContract, $this>
     */
    public function toolContract(): BelongsTo
    {
        return $this->belongsTo(ToolContract::class);
    }

    /**
     * Get the user that triggered the version (null for system/SDK changes).
     *
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'execution_mode' => ExecMode::class,
            'input_schema' => 'array',
            'output_schema' => 'array',
            'config' => 'encrypted:array',
        ];
    }
}
