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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->longText('title');
            $table->longText('slug');
            $table->longText('description')->nullable();
            $table->longText('comments')->nullable();
            $table->longText('identifier')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('image', 2048)->nullable();
            $table->string('photo', 2048)->nullable();
            $table->boolean('is_public')->default(0);
            $table->uuid('uuid')->unique();
            $table->enum('status', ['DRAFT', 'REVIEW', 'EMBARGO', 'PUBLISHED', 'REJECTED'])->default('DRAFT');
            $table->enum('jobs_status', ['INCURATION', 'QUEUED', 'PROCESSING', 'COMPLETE'])->default('INCURATION');
            $table->longText('job_info')->nullable();
            $table->longText('doi')->nullable();
            $table->foreignId('owner_id')->nullable();
            $table->foreignId('license_id')->nullable();
            $table->timestamp('release_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
