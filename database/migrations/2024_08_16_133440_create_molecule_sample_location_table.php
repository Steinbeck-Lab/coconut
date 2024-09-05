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
        Schema::create('molecule_sample_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('molecule_id')->constrained()->onDelete('cascade');
            $table->foreignId('sample_location_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('sample_locations', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('molecule_sample_location');

        Schema::table('sample_locations', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
};
