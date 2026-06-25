<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The secrets vault: the governed system of record for the platform's sensitive
 * credential material (LLM provider keys, application credentials, remote tool
 * secrets, webhook signing secrets, connector credentials). The value is stored
 * encrypted at rest; rotation bumps the version and stamps `rotated_at`, and
 * every read updates the access counters so secret usage is traceable.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vault_secrets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('name');
            $table->string('kind')->default('generic');
            $table->text('ciphertext');
            $table->string('last_four')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('rotated_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedBigInteger('accessed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'reference']);
            $table->index(['team_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_secrets');
    }
};
