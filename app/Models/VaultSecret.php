<?php

namespace App\Models;

use App\Concerns\RecordsAuditEvents;
use App\Enums\VaultSecretKind;
use App\Support\Secrets\Contracts\SecretVault;
use Database\Factories\VaultSecretFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * A vault-held secret: the governed, encrypted-at-rest system of record for a
 * piece of sensitive credential material. The plaintext is held only in the
 * `ciphertext` column (encrypted) and is never serialized; reads go through the
 * {@see SecretVault} so usage is tracked and
 * rotation is versioned.
 *
 * @property string $id
 * @property int $team_id
 * @property string $reference
 * @property string $name
 * @property VaultSecretKind $kind
 * @property string $ciphertext
 * @property string|null $last_four
 * @property int $version
 * @property Carbon|null $rotated_at
 * @property Carbon|null $last_accessed_at
 * @property int $accessed_count
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Team $team
 * @property-read User|null $creator
 */
#[Fillable(['team_id', 'reference', 'name', 'kind', 'ciphertext', 'last_four', 'version', 'rotated_at', 'last_accessed_at', 'accessed_count', 'created_by'])]
#[Hidden(['ciphertext'])]
class VaultSecret extends Model
{
    /** @use HasFactory<VaultSecretFactory> */
    use HasFactory, HasUuids, RecordsAuditEvents, SoftDeletes;

    /**
     * Get the team that owns the secret.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that registered the secret.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the LLM providers whose API key is resolved from this secret.
     *
     * @return HasMany<LlmProvider, $this>
     */
    public function llmProviders(): HasMany
    {
        return $this->hasMany(LlmProvider::class, 'vault_secret_id');
    }

    /**
     * Mark the secret as accessed (used to trace secret usage), persisting the
     * counters quietly so a read does not generate an audit "updated" event.
     */
    public function markAccessed(): void
    {
        $this->forceFill([
            'last_accessed_at' => Date::now(),
            'accessed_count' => $this->accessed_count + 1,
        ])->saveQuietly();
    }

    /**
     * Resolve the team this secret is audited under.
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
            'kind' => VaultSecretKind::class,
            'ciphertext' => 'encrypted',
            'version' => 'integer',
            'accessed_count' => 'integer',
            'rotated_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }
}
