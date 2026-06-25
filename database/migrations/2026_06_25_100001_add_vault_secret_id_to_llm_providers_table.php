<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an approved LLM provider to a vault-held API key. When set, the runtime
 * resolves the provider's key from the secrets vault at call time (so the key can
 * be rotated centrally without redeploying); when null, the provider falls back
 * to the environment/config-driven key as before.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('llm_providers', function (Blueprint $table): void {
            $table->uuid('vault_secret_id')->nullable()->after('status');
            $table->foreign('vault_secret_id')->references('id')->on('vault_secrets')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llm_providers', function (Blueprint $table): void {
            $table->dropForeign(['vault_secret_id']);
            $table->dropColumn('vault_secret_id');
        });
    }
};
