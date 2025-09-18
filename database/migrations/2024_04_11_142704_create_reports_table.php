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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 2048)->nullable();
            $table->longText('title');
            $table->longText('evidence')->nullable();
            $table->string('url', 2048)->nullable();
            $table->longText('mol_id_csv')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->longText('comment')->nullable();
            $table->foreignId('user_id');
            $table->boolean('is_change')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');

    }
};
