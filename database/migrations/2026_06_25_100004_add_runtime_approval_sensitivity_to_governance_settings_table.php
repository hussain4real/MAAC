<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A team-wide policy threshold: runs whose data sensitivity is at or above this
 * level require human approval before executing, in addition to any agent that is
 * individually flagged. Null disables the sensitivity-based gate.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('governance_settings', function (Blueprint $table): void {
            $table->string('runtime_approval_sensitivity')->nullable()->after('default_daily_run_quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('governance_settings', function (Blueprint $table): void {
            $table->dropColumn('runtime_approval_sensitivity');
        });
    }
};
