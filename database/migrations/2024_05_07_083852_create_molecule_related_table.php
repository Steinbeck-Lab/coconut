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
        Schema::create('molecule_related', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('molecule_id');
            $table->unsignedInteger('related_id');
            $table->text('type')->nullable;
            $table->foreign('molecule_id')->references('id')->on('molecules');
            $table->foreign('related_id')->references('id')->on('molecules');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('molecule_related');
    }
};
