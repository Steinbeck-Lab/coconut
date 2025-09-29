<?php

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_status');

        // Add the updated check constraint with all ReportStatus enum values
        DB::statement('ALTER TABLE reports ADD CONSTRAINT check_status CHECK (status IN (\''.implode('\',\'', ReportStatus::values()).'\'))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any records with PENDING_APPROVAL or PENDING_REJECTION to SUBMITTED
        // to make them compatible with the old constraint
        DB::statement("UPDATE reports SET status = 'SUBMITTED' WHERE status IN ('PENDING_APPROVAL', 'PENDING_REJECTION')");

        // Drop the current constraint
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_status');

        // Restore the old constraint without PENDING_APPROVAL and PENDING_REJECTION
        DB::statement("ALTER TABLE reports ADD CONSTRAINT check_status CHECK ((status)::text = ANY (ARRAY[('SUBMITTED'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('COMPLETED'::character varying)::text, ('INPROGRESS'::character varying)::text]))");
    }
};
