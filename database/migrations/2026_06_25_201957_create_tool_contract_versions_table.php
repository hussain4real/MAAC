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
        Schema::create('tool_contract_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tool_contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('version', 32);
            $table->string('execution_mode');
            $table->string('schema_fingerprint', 64);
            $table->json('input_schema');
            $table->json('output_schema');
            $table->json('config')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tool_contract_id', 'sequence']);
            $table->unique(['tool_contract_id', 'version']);
            $table->index(['tool_contract_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_contract_versions');
    }
};
