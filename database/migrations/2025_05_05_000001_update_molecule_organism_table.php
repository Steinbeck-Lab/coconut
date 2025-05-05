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
            $table->dropColumn('organism_parts');

            // Add sample_location_id foreign key
            $table->foreignId('sample_location_id')->nullable()->constrained()->onDelete('cascade');

            // add citation_ids column
            $table->string('citation_ids')->nullable()->after('sample_location_id');

            // Drop old unique index if it exists
            $table->dropPrimary('molecule_organism_pkey');

            // Add new unique constraint including sample_location_id
            $table->unique(['molecule_id', 'organism_id', 'sample_location_id'], 'molecule_organism_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            // Remove the new unique constraint
            $table->dropUnique('molecule_organism_unique');

            // Remove sample_location_id foreign key
            $table->dropForeign(['sample_location_id']);
            $table->dropColumn(['sample_location_id', 'citation_ids']);

            // Restore organism_parts column
            $table->longText('organism_parts')->nullable();

            // Restore original unique constraint
            $table->primary(['molecule_id', 'organism_id'], 'molecule_organism_pkey');
        });
    }
};
