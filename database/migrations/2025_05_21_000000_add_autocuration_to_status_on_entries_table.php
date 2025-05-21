<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, find the existing constraint name for the status column
        $result = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            INNER JOIN pg_class rel ON rel.oid = con.conrelid
            INNER JOIN pg_attribute att ON att.attrelid = rel.oid AND att.attnum = con.conkey[1]
            WHERE rel.relname = 'entries' AND att.attname = 'status' AND con.contype = 'c'
        ");

        if (! empty($result)) {
            $constraintName = $result[0]->conname;

            // Drop the existing constraint
            DB::statement("ALTER TABLE entries DROP CONSTRAINT $constraintName");
        }

        // Define the new set of allowed values including AUTOCURATION
        $statusValues = ['SUBMITTED', 'PROCESSING', 'INREVIEW', 'PASSED', 'AUTOCURATION', 'REJECTED'];

        // Add a new constraint with the updated enum values
        DB::statement("ALTER TABLE entries ADD CONSTRAINT $constraintName CHECK (status IN ('".implode('\',\'', $statusValues)."'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Find the existing constraint name
        $result = DB::select("
            SELECT con.conname
            FROM pg_constraint con
            INNER JOIN pg_class rel ON rel.oid = con.conrelid
            INNER JOIN pg_attribute att ON att.attrelid = rel.oid AND att.attnum = con.conkey[1]
            WHERE rel.relname = 'entries' AND att.attname = 'status' AND con.contype = 'c'
        ");

        if (! empty($result)) {
            $constraintName = $result[0]->conname;

            // Drop the constraint we added
            DB::statement("ALTER TABLE entries DROP CONSTRAINT $constraintName");

            // Add back the original constraint without AUTOCURATION
            $originalStatusValues = ['SUBMITTED', 'PROCESSING', 'INREVIEW', 'PASSED', 'REJECTED'];
            DB::statement("ALTER TABLE entries ADD CONSTRAINT $constraintName CHECK (status IN ('".implode('\',\'', $originalStatusValues)."'))");
        }
    }
};
