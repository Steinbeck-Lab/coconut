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
        Schema::table('properties', function (Blueprint $table) {
            $table->text('pathway_results')->nullable();
            $table->text('superclass_results')->nullable();
            $table->text('class_results')->nullable();
            $table->boolean('isglycoside')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['pathway_results', 'superclass_results', 'class_results', 'isglycoside']);
        });
    }
};
