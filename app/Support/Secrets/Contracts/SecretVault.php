<?php

namespace App\Support\Secrets\Contracts;

use App\Enums\VaultSecretKind;
use App\Models\Team;
use App\Models\User;
use App\Models\VaultSecret;
use App\Support\Secrets\DatabaseSecretVault;

/**
 * The platform secrets vault: a single, governed chokepoint for storing,
 * reading, rotating, and forgetting sensitive credential material. The default
 * binding is the database-backed {@see DatabaseSecretVault}
 * (encrypted at rest); an enterprise deployment can bind an external
 * vault driver (HashiCorp Vault, AWS Secrets Manager, …) without changing any
 * caller, exactly as the LLM Router and knowledge retriever are swappable.
 */
interface SecretVault
{
    /**
     * Store new secret material under a stable, team-unique reference, returning
     * the vault record. If the reference already exists it is rotated in place.
     */
    public function store(Team $team, string $reference, string $name, VaultSecretKind $kind, string $plaintext, ?User $actor = null): VaultSecret;

    /**
     * Reveal the plaintext value of a secret, recording the access so secret
     * usage is traceable.
     */
    public function read(VaultSecret $secret): string;

    /**
     * Rotate a secret to new material, bumping its version and stamping the
     * rotation time. The reference and any bindings are preserved.
     */
    public function rotate(VaultSecret $secret, string $plaintext): VaultSecret;

    /**
     * Permanently forget (soft delete) a secret so it can no longer be read.
     */
    public function forget(VaultSecret $secret): void;
}
