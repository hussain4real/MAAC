<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advanced model routing policies. A policy targets one agent and defines how the
 * runtime selects the model for a run: an ordered candidate chain (a primary plus
 * fallbacks), a strategy (cost / latency / balanced), and ceilings on cost and
 * latency. The router additionally filters every candidate by environment
 * availability, sensitivity clearance, and recent provider health, and fails over
 * along the chain when a model call errors mid-run.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('model_routing_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('agent_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('strategy')->default('balanced');
            $table->foreignUuid('primary_provider_id')->nullable()->constrained('llm_providers')->nullOnDelete();
            $table->json('fallback_provider_ids')->nullable();
            $table->decimal('max_cost_per_1k', 8, 4)->nullable();
            $table->unsignedInteger('max_latency_ms')->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_routing_policies');
    }
};
