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
        Schema::table('sample_locations', function (Blueprint $table) {
            $table->dropColumn(['organism_id', 'collection_ids', 'molecule_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sample_locations', function (Blueprint $table) {
            $table->foreignId('organism_id')->nullable();
            $table->text('collection_ids')->nullable();
            $table->integer('molecule_count')->nullable();
        });
    }
};
