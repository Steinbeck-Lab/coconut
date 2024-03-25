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
        Schema::create('collection_molecule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id');
            $table->foreignId('molecule_id');
            $table->string('url')->nullable();
            $table->string('reference')->nullable();
            $table->unique(['collection_id', 'molecule_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_molecule');
    }
};
