<?php

use App\Enums\VaultSecretKind;
use App\Models\LlmProvider;
use App\Models\VaultSecret;
use App\Support\Secrets\Contracts\SecretVault;
use App\Support\Secrets\DatabaseSecretVault;

test('the database vault binding resolves the default driver', function () {
    expect(app(SecretVault::class))->toBeInstanceOf(DatabaseSecretVault::class);
});

test('storing a secret encrypts it at rest and records metadata', function () {
    [$owner, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);

    $secret = $vault->store($team, 'llm_key:claude', 'Claude key', VaultSecretKind::LlmKey, 'sk-ant-supersecret', $owner);

    expect($secret->team_id)->toBe($team->id)
        ->and($secret->kind)->toBe(VaultSecretKind::LlmKey)
        ->and($secret->version)->toBe(1)
        ->and($secret->last_four)->toBe('cret')
        ->and($secret->created_by)->toBe($owner->getAuthIdentifier())
        ->and($secret->ciphertext)->toBe('sk-ant-supersecret')
        ->and($secret->getRawOriginal('ciphertext'))->not->toBe('sk-ant-supersecret');
});

test('storing under an existing reference rotates the secret in place', function () {
    [, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);

    $first = $vault->store($team, 'llm_key:claude', 'Claude key', VaultSecretKind::LlmKey, 'sk-one');
    $second = $vault->store($team, 'llm_key:claude', 'Claude key v2', VaultSecretKind::LlmKey, 'sk-two');

    expect($second->id)->toBe($first->id)
        ->and($second->version)->toBe(2)
        ->and($second->name)->toBe('Claude key v2')
        ->and($team->vaultSecrets()->count())->toBe(1)
        ->and($vault->read($second->fresh()))->toBe('sk-two');
});

test('reading a secret returns the plaintext and records the access without an audit event', function () {
    [, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);
    $secret = $vault->store($team, 'generic:x', 'X', VaultSecretKind::Generic, 'plaintext-value');

    $auditBefore = $team->auditEvents()->count();

    expect($vault->read($secret))->toBe('plaintext-value');

    $fresh = $secret->fresh();
    expect($fresh->accessed_count)->toBe(1)
        ->and($fresh->last_accessed_at)->not->toBeNull()
        ->and($team->auditEvents()->count())->toBe($auditBefore);
});

test('rotating a secret bumps the version and stamps the rotation time', function () {
    [, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);
    $secret = $vault->store($team, 'generic:y', 'Y', VaultSecretKind::Generic, 'old');

    $rotated = $vault->rotate($secret, 'new-secret');

    expect($rotated->version)->toBe(2)
        ->and($rotated->rotated_at)->not->toBeNull()
        ->and($rotated->last_four)->toBe('cret')
        ->and($vault->read($rotated->fresh()))->toBe('new-secret');
});

test('forgetting a secret soft deletes it', function () {
    [, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);
    $secret = $vault->store($team, 'generic:z', 'Z', VaultSecretKind::Generic, 'gone');

    $vault->forget($secret);

    expect($secret->fresh()->trashed())->toBeTrue();
});

test('an LLM provider resolves its API key from the bound vault secret, else null', function () {
    [, $team] = ownerAndTeam();
    $vault = app(SecretVault::class);
    $secret = VaultSecret::factory()->for($team)->llmKey()->withValue('sk-bound-key')->create();

    $bound = LlmProvider::factory()->for($team)->create(['vault_secret_id' => $secret->id]);
    $unbound = LlmProvider::factory()->for($team)->create();

    expect($bound->resolveApiKey($vault))->toBe('sk-bound-key')
        ->and($unbound->resolveApiKey($vault))->toBeNull();
});
