<?php

namespace App\Models;

use App\Enums\Environment;
use App\Enums\IncidentActionType;
use Database\Factories\IncidentActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One break-glass / incident-response action: an emergency control an operator
 * triggered (with a mandatory reason) to contain an incident. The record is the
 * incident timeline — it is never deleted, and a freeze can be reverted (which
 * stamps `reverted_at`).
 *
 * @property string $id
 * @property int $team_id
 * @property int|null $actor_user_id
 * @property string|null $actor_label
 * @property IncidentActionType $type
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string|null $subject_label
 * @property string $reason
 * @property Environment|null $environment
 * @property Carbon|null $reverted_at
 * @property int|null $reverted_by
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User|null $actor
 */
#[Fillable(['team_id', 'actor_user_id', 'actor_label', 'type', 'subject_type', 'subject_id', 'subject_label', 'reason', 'environment', 'reverted_at', 'reverted_by', 'metadata'])]
class IncidentAction extends Model
{
    /** @use HasFactory<IncidentActionFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the team the incident action belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the operator that triggered the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Get the affected subject (credential, model, connector, webhook, …).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Whether the action has been reverted (only freezes can be reverted).
     */
    public function isReverted(): bool
    {
        return $this->reverted_at !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentActionType::class,
            'environment' => Environment::class,
            'reverted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
