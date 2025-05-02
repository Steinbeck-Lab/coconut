<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('report_category', ['report', 'change', 'new_molecule'])->nullable()->after('report_type');
        });

        // Migrate existing data
        DB::table('reports')->where('is_change', true)->update(['report_category' => 'change']);
        DB::table('reports')->where('is_change', false)->update(['report_category' => 'report']);

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('is_change');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('is_change')->nullable()->after('report_type');
            // Restore data
            DB::table('reports')->where('report_category', 'change')->update(['is_change' => true]);
            DB::table('reports')->where('report_category', 'report')->update(['is_change' => false]);
            $table->dropColumn('report_category');
        });
    }
};
