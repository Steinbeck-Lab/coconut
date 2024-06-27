<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filament_filter_sets', function (Blueprint $table) {
            $table->string('status')->after('is_global_favorite')->default('approved');
        });
    }

    public function down(): void
    {
        Schema::table('filament_filter_sets', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
