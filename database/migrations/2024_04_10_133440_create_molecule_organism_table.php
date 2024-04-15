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
        Schema::create('molecule_organism', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organism_id')->constrained()->onDelete('cascade');
            $table->foreignId('molecule_id')->constrained()->onDelete('cascade');
            $table->longText('organism_parts');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('molecule_organism');
    }
};
