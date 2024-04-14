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
        Schema::create('reportables', function (Blueprint $table) {
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            $table->morphs('reportable');

            $table->unique(['report_id', 'reportable_id', 'reportable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reportables');
    }
};
