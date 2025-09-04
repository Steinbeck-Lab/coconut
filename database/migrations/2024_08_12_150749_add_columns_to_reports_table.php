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
        Schema::table('reports', function (Blueprint $table) {
            // is_change column already exists from create_reports_table migration
            // Only add suggested_changes column
            $table->json('suggested_changes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Only drop suggested_changes column, keep is_change as it belongs to the original table
            $table->dropColumn('suggested_changes');
        });
    }
};
