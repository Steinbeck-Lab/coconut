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
        Schema::rename('organism_parts', 'sample_locations');
        Schema::table('sample_locations', function (Blueprint $table) {
            $table->string('collection_ids')->nullable();
            $table->integer('molecule_count')->nullable();
            $table->string('iri')->nullable()->change();
        });
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->string('collection_ids')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->dropColumn(['collection_ids']);
        });
        Schema::table('sample_locations', function (Blueprint $table) {
            $table->renameColumn('iri', 'ontology');
            $table->dropColumn(['collection_ids', 'molecule_count']);
        });
        Schema::rename('sample_locations', 'organism_parts');
    }
};
