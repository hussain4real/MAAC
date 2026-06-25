<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a local user to an external identity from an SSO connection (keyed by the
 * provider's stable subject claim), so a returning user is recognized and a
 * security reviewer can trace which external identity a user authenticated with.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sso_identities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sso_connection_id')->constrained('sso_connections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('email')->nullable();
            $table->json('raw_claims')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['sso_connection_id', 'subject']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_identities');
    }
};
