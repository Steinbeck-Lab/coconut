<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filament_filter_sets_managed_preset_views', function (Blueprint $table) {
            $table->integer('tenant_id')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('filament_filter_sets_managed_preset_views', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
