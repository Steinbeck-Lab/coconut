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
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->longText('canonical_smiles')->nullable();
            $table->longText('identifier')->nullable();
            $table->longText('doi')->nullable();
            $table->longText('link')->nullable();
            $table->longText('organism')->nullable();
            $table->longText('organism_part')->nullable();
            $table->text('molecular_formula')->nullable();
            $table->longText('coconut_id')->nullable();
            $table->longText('mol_filename')->nullable();
            $table->enum('status', ['SUBMITTED', 'PROCESSING', 'INREVIEW', 'PASSED', 'REJECTED'])->default('SUBMITTED');
            $table->longText('structural_comments')->nullable();
            $table->foreignId('owner_id')->nullable();
            $table->foreignId('collection_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->longText('errors')->nullable();
            $table->integer('error_code')->default(7)->nullable();
            $table->longText('standardized_canonical_smiles')->nullable();
            $table->longText('parent_canonical_smiles')->nullable();
            $table->boolean('is_invalid')->default(0);
            $table->boolean('has_stereocenters')->default(0);
            $table->foreignId('molecule_id')->nullable();
            $table->json('cm_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
