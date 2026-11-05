<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLUMNS = [
        'np_classifier_pathway',
        'np_classifier_superclass',
        'np_classifier_class',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::statement("
                ALTER TABLE properties
                ALTER COLUMN {$column} TYPE jsonb
                USING (
                    CASE
                        WHEN {$column} IS NULL OR TRIM({$column}::text) = '' THEN NULL
                        WHEN TRIM({$column}::text) LIKE '[%' THEN TRIM({$column}::text)::jsonb
                        ELSE to_jsonb(ARRAY[{$column}::text])
                    END
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::statement("
                ALTER TABLE properties
                ALTER COLUMN {$column} TYPE text
                USING (
                    CASE
                        WHEN {$column} IS NULL THEN NULL
                        WHEN jsonb_typeof({$column}) = 'array' AND jsonb_array_length({$column}) > 0
                            THEN (SELECT string_agg(value, ', ' ORDER BY ordinality)
                                  FROM jsonb_array_elements_text({$column}) WITH ORDINALITY AS t(value, ordinality))
                        ELSE {$column}::text
                    END
                )
            ");
        }
    }
};
