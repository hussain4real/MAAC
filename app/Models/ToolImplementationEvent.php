<?php

namespace App\Models;

use App\Enums\Environment;
use App\Enums\ImplementationEventReason;
use App\Enums\ImplStatus;
use Database\Factories\ToolImplementationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only entry in a client-side tool's implementation timeline. A row is
 * written on every SDK report and on every contract-change reconcile, capturing
 * the resulting status transition (and the contract version it was evaluated
 * against) so the consumer's journey — required → implemented → outdated →
 * incompatible → recovered — is queryable over time.
 *
 * @property string $id
 * @property string $tool_contract_id
 * @property string $application_id
 * @property string|null $tool_implementation_id
 * @property string|null $tool_contract_version_id
 * @property Environment $environment
 * @property ImplStatus $status
 * @property ImplStatus|null $previous_status
 * @property ImplementationEventReason $reason
 * @property string|null $reported_version
 * @property string|null $schema_fingerprint
 * @property string $contract_version
 * @property int|null $actor_user_id
 * @property string|null $actor_label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ToolContract $toolContract
 * @property-read Application $application
 * @property-read ToolImplementation|null $toolImplementation
 * @property-read ToolContractVersion|null $contractVersion
 * @property-read User|null $actor
 */
#[Fillable(['tool_contract_id', 'application_id', 'tool_implementation_id', 'tool_contract_version_id', 'environment', 'status', 'previous_status', 'reason', 'reported_version', 'schema_fingerprint', 'contract_version', 'actor_user_id', 'actor_label'])]
class ToolImplementationEvent extends Model
{
    /** @use HasFactory<ToolImplementationEventFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the contract the event was recorded against.
     *
     * @return BelongsTo<ToolContract, $this>
     */
    public function toolContract(): BelongsTo
    {
        return $this->belongsTo(ToolContract::class);
    }

    /**
     * Get the application whose handler the event describes.
     *
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the current implementation row the event was derived from (if still present).
     *
     * @return BelongsTo<ToolImplementation, $this>
     */
    public function toolImplementation(): BelongsTo
    {
        return $this->belongsTo(ToolImplementation::class);
    }

    /**
     * Get the contract version snapshot the event was evaluated against.
     *
     * @return BelongsTo<ToolContractVersion, $this>
     */
    public function contractVersion(): BelongsTo
    {
        return $this->belongsTo(ToolContractVersion::class, 'tool_contract_version_id');
    }

    /**
     * Get the user that triggered the event (null for SDK-reported events).
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'environment' => Environment::class,
            'status' => ImplStatus::class,
            'previous_status' => ImplStatus::class,
            'reason' => ImplementationEventReason::class,
        ];
    }
}
