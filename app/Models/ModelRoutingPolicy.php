<?php

namespace App\Models;

use App\Concerns\RecordsAuditEvents;
use App\Enums\RoutingStrategy;
use Database\Factories\ModelRoutingPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An advanced model routing policy for a single agent. It names the ordered
 * candidate models (a primary plus a fallback chain) and the constraints — a
 * strategy plus cost and latency ceilings — that the {@see
 * \App\Support\Runtime\Routing\ModelRouter} applies to pick the model for a run
 * and to fail over when a model call errors.
 *
 * @property string $id
 * @property int $team_id
 * @property string $agent_id
 * @property string $name
 * @property RoutingStrategy $strategy
 * @property string|null $primary_provider_id
 * @property array<int, string>|null $fallback_provider_ids
 * @property float|null $max_cost_per_1k
 * @property int|null $max_latency_ms
 * @property bool $enabled
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Agent $agent
 * @property-read LlmProvider|null $primaryProvider
 * @property-read User|null $creator
 */
#[Fillable(['team_id', 'agent_id', 'name', 'strategy', 'primary_provider_id', 'fallback_provider_ids', 'max_cost_per_1k', 'max_latency_ms', 'enabled', 'created_by'])]
class ModelRoutingPolicy extends Model
{
    /** @use HasFactory<ModelRoutingPolicyFactory> */
    use HasFactory, HasUuids, RecordsAuditEvents;

    /**
     * Get the team that owns the policy.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the agent the policy routes.
     *
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the policy's primary model (defaults to the agent's model when null).
     *
     * @return BelongsTo<LlmProvider, $this>
     */
    public function primaryProvider(): BelongsTo
    {
        return $this->belongsTo(LlmProvider::class, 'primary_provider_id');
    }

    /**
     * Get the user that created the policy.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The ordered candidate model ids: the primary (or the agent's model when no
     * primary is set) followed by the fallback chain, de-duplicated.
     *
     * @return array<int, string>
     */
    public function candidateProviderIds(): array
    {
        $primary = $this->primary_provider_id ?? $this->agent->llm_provider_id;

        return collect([$primary, ...($this->fallback_provider_ids ?? [])])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the team this policy is audited under.
     */
    protected function auditTeam(): ?Team
    {
        return $this->team;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'strategy' => RoutingStrategy::class,
            'fallback_provider_ids' => 'array',
            'max_cost_per_1k' => 'float',
            'max_latency_ms' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
