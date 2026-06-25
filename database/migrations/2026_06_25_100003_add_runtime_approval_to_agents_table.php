<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks an agent as requiring human-in-the-loop approval before any of its runs
 * may execute. When set, the runtime pauses every run at `requires_approval` and
 * opens a governance approval; a reviewer approves to resume or rejects to fail.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->boolean('requires_runtime_approval')->default(false)->after('sensitivity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table): void {
            $table->dropColumn('requires_runtime_approval');
        });
    }
};
