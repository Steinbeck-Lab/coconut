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
        Schema::create('geo_location_organism', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_location_id')->constrained();
            $table->foreignId('organism_id')->constrained();
            $table->timestamps();

            $table->unique(['geo_location_id', 'organism_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_location_organism');
    }
};
