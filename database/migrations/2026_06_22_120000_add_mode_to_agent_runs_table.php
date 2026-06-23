<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records how a run was invoked so the runtime can distinguish a synchronous
 * (request-blocking) run from an asynchronous (worker-backed) one. Existing rows
 * default to `sync`, preserving the prior behaviour.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agent_runs', function (Blueprint $table): void {
            $table->string('mode')->default('sync')->after('caller');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_runs', function (Blueprint $table): void {
            $table->dropColumn('mode');
        });
    }
};
