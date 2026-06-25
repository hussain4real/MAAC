<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A break-glass runtime freeze for an application: while `runtime_frozen_at` is
 * set, the runtime rejects new runs and halts in-flight ones for that
 * application. An operator lifts the freeze to resume normal operation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('runtime_frozen_at')->nullable()->after('status');
            $table->foreignId('runtime_frozen_by')->nullable()->after('runtime_frozen_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropForeign(['runtime_frozen_by']);
            $table->dropColumn(['runtime_frozen_at', 'runtime_frozen_by']);
        });
    }
};
