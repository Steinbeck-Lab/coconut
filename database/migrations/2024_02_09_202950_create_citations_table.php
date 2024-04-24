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
        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->longText('doi')->nullable()->unique();
            $table->longText('title')->nullable();
            $table->longText('authors')->nullable();
            $table->longText('citation_text')->nullable();
            $table->boolean('active')->default(0);
            $table->timestamps();
        });

        Schema::create('citables', function (Blueprint $table) {
            $table->foreignId('citation_id')->constrained()->cascadeOnDelete();

            $table->morphs('citable');

            $table->unique(['citation_id', 'citable_id', 'citable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citables');
        Schema::dropIfExists('citations');
    }
};
