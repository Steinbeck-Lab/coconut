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
        Schema::create('geo_location_molecule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geo_location_id')->constrained()->onDelete('cascade');
            $table->foreignId('molecule_id')->constrained()->onDelete('cascade');
            $table->longText('locations');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_location_molecule');
    }
};
