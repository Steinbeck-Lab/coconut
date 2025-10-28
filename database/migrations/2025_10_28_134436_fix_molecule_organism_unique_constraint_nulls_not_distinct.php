<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old unique constraint that treats NULLs as distinct
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->dropUnique('unique_molecule_organism_complete');
        });

        // Create new unique constraint with NULLS NOT DISTINCT
        // This makes NULL values equal to each other, preventing duplicate NULL combinations
        DB::statement('
            CREATE UNIQUE INDEX unique_molecule_organism_complete 
            ON molecule_organism (
                molecule_id, 
                organism_id, 
                sample_location_id, 
                geo_location_id, 
                ecosystem_id
            ) NULLS NOT DISTINCT
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the NULLS NOT DISTINCT constraint
        DB::statement('DROP INDEX IF EXISTS unique_molecule_organism_complete');

        // Recreate the old constraint (with NULLs treated as distinct)
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->unique(
                ['molecule_id', 'organism_id', 'sample_location_id', 'geo_location_id', 'ecosystem_id'],
                'unique_molecule_organism_complete'
            );
        });
    }
};
