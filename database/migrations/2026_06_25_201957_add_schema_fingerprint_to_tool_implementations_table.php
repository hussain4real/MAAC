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
        Schema::table('tool_implementations', function (Blueprint $table) {
            // The schema fingerprint the application reported its handler against,
            // so a later contract schema edit that does not bump the version is
            // still detectable as drift (Incompatible) on reconcile.
            $table->string('schema_fingerprint', 64)->nullable()->after('implemented_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tool_implementations', function (Blueprint $table) {
            $table->dropColumn('schema_fingerprint');
        });
    }
};
