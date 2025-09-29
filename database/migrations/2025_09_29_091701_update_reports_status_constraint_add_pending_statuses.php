<?php

use App\Enums\ReportStatus;
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
        // Drop the current constraint
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_status');
        
        // Restore the old constraint without PENDING_APPROVAL and PENDING_REJECTION
        DB::statement("ALTER TABLE reports ADD CONSTRAINT check_status CHECK ((status)::text = ANY (ARRAY[('SUBMITTED'::character varying)::text, ('APPROVED'::character varying)::text, ('REJECTED'::character varying)::text, ('COMPLETED'::character varying)::text, ('INPROGRESS'::character varying)::text]))");
    }
};
