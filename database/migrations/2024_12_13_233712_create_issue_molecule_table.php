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
        Schema::create('issue_molecule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id');
            $table->foreignId('molecule_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_resolved')->default(false);
            $table->jsonb('meta_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_molecule');
    }
};
