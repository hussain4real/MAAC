<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tool_implementation_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tool_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('application_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('tool_implementation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('tool_contract_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment');
            $table->string('status');
            $table->string('previous_status')->nullable();
            $table->string('reason');
            $table->string('reported_version', 32)->nullable();
            $table->string('schema_fingerprint', 64)->nullable();
            $table->string('contract_version', 32);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->timestamps();

            $table->index(['tool_contract_id', 'created_at']);
            $table->index(['application_id', 'environment', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_implementation_events');
    }
};
