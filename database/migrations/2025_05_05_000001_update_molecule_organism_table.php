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
            // Drop existing organism_parts column
            // $table->dropColumn('organism_parts');

            // Add sample_location_id foreign key
            $table->foreignId('sample_location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('geo_location_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('ecosystem_id')->nullable()->constrained()->onDelete('cascade');

            // add citation_ids column
            $table->text('citation_ids')->nullable()->after('sample_location_id');

            // Drop old unique index if it exists
            // $table->dropPrimary('molecule_organism_pkey');

            // Add new unique constraint that includes all identifying fields
            $table->unique(
                ['molecule_id', 'organism_id', 'sample_location_id', 'geo_location_id', 'ecosystem_id'],
                'unique_molecule_organism_complete'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            // Remove the new unique constraint
            $table->dropUnique('unique_molecule_organism_complete');

            // Remove sample_location_id foreign key
            $table->dropForeign(['sample_location_id']);
            $table->dropForeign(['geo_location_id']);
            $table->dropForeign(['ecosystem_id']);
            $table->dropColumn(['sample_location_id', 'geo_location_id', 'ecosystem_id', 'citation_ids']);

            // Restore organism_parts column
            // $table->longText('organism_parts')->nullable();

            // Restore original unique constraint
            // $table->primary(['molecule_id', 'organism_id'], 'molecule_organism_pkey');
        });
    }
};
