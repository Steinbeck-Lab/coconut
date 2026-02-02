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
        Schema::table('ecosystems', function (Blueprint $table) {
            $table->foreignId('geo_location_id')->nullable()->constrained('geo_locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecosystems', function (Blueprint $table) {
            $table->dropForeign(['geo_location_id']);
            $table->dropColumn('geo_location_id');
        });
    }
};
