<?php

namespace App\Support\Secrets;

use App\Enums\VaultSecretKind;
use App\Models\Team;
use App\Models\User;
use App\Models\VaultSecret;
use App\Support\Secrets\Contracts\SecretVault;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * The default secrets vault: encrypts secret material at rest in the
 * `vault_secrets` table (via the model's `encrypted` cast), versions rotations,
 * and tracks access. This is the "local placeholder" an enterprise deployment
 * replaces by binding an external {@see SecretVault} driver; every caller goes
 * through the interface so that swap is transparent.
 */
class DatabaseSecretVault implements SecretVault
{
    /**
     * Store new secret material under a stable, team-unique reference. If a
     * secret already exists for the reference, it is rotated in place.
     */
    public function store(Team $team, string $reference, string $name, VaultSecretKind $kind, string $plaintext, ?User $actor = null): VaultSecret
    {
        $existing = $team->vaultSecrets()->where('reference', $reference)->first();

        if ($existing instanceof VaultSecret) {
            $existing->update(['name' => $name, 'kind' => $kind]);

            return $this->rotate($existing, $plaintext);
        }

        return $team->vaultSecrets()->create([
            'reference' => $reference,
            'name' => $name,
            'kind' => $kind,
            'ciphertext' => $plaintext,
            'last_four' => $this->lastFour($plaintext),
            'version' => 1,
            'created_by' => $actor?->getAuthIdentifier(),
        ]);
    }

    /**
     * Reveal the plaintext value of a secret, recording the access.
     */
    public function read(VaultSecret $secret): string
    {
        $secret->markAccessed();

        return $secret->ciphertext;
    }

    /**
     * Rotate a secret to new material, bumping its version.
     */
    public function rotate(VaultSecret $secret, string $plaintext): VaultSecret
    {
        $secret->update([
            'ciphertext' => $plaintext,
            'last_four' => $this->lastFour($plaintext),
            'version' => $secret->version + 1,
            'rotated_at' => Date::now(),
        ]);

        return $secret;
    }

    /**
     * Forget (soft delete) a secret so it can no longer be read.
     */
    public function forget(VaultSecret $secret): void
    {
        $secret->delete();
    }

    /**
     * Derive the display hint (last characters) for a plaintext value.
     */
    private function lastFour(string $plaintext): string
    {
        return Str::substr($plaintext, -4);
    }
}
