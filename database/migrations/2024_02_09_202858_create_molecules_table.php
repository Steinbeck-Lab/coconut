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
        Schema::create('molecules', function (Blueprint $table) {
            $table->id();

            $table->longText('inchi')->nullable();
            $table->longText('standard_inchi')->unique();
            $table->longText('inchi_key')->nullable();
            $table->longText('standard_inchi_key')->nullable();
            $table->longText('canonical_smiles')->nullable();
            $table->longText('sugar_free_smiles')->nullable();
            $table->longText('identifier')->nullable()->unique();
            $table->longText('name')->nullable();
            $table->longText('cas')->nullable();
            $table->json('synonyms')->nullable();
            $table->longText('iupac_name')->nullable();
            $table->longText('murko_framework')->nullable();
            $table->longText('structural_comments')->nullable();

            $table->integer('name_trust_level')->default(0)->nullable();
            $table->integer('annotation_level')->default(0)->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('variants_count')->default(0);

            $table->integer('ticker')->default(0);

            $table->enum('status', ['DRAFT', 'APPROVED', 'REVOKED', 'INREVIEW'])->default('DRAFT');

            $table->boolean('active')->default(0);
            $table->boolean('has_variants')->default(0);
            $table->boolean('has_stereo')->default(0);
            $table->boolean('is_parent')->default(0);
            $table->boolean('is_placeholder')->default(0);

            $table->json('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('molecules');
    }
};
