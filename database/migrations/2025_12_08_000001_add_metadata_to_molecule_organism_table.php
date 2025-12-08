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
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->jsonb('metadata')->nullable()->after('citation_ids');
            $table->dropColumn([
                'organism_parts',
                'sample_location_id',
                'geo_location_id',
                'ecosystem_id',
            ]);
            $table->dropUnique('unique_molecule_organism_complete');
            $table->unique(['molecule_id', 'organism_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->dropColumn('metadata');
            $table->string('organism_parts')->nullable();
            $table->unsignedBigInteger('sample_location_id')->nullable();
            $table->unsignedBigInteger('geo_location_id')->nullable();
            $table->unsignedBigInteger('ecosystem_id')->nullable();
            $table->dropUnique(['molecule_id', 'organism_id']);
            $table->unique('unique_molecule_organism_complete');
        });
    }
};
