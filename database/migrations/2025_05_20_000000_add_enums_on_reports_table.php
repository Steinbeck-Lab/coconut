<?php

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $result = DB::select("
                SELECT con.conname
                FROM pg_constraint con
                INNER JOIN pg_class rel ON rel.oid = con.conrelid
                INNER JOIN pg_attribute att ON att.attrelid = rel.oid AND att.attnum = con.conkey[1]
                WHERE rel.relname = 'reports' AND att.attname = 'report_category' AND con.contype = 'c'
            ");

        $contraintName = $result[0]->conname;

        DB::statement("ALTER TABLE reports DROP CONSTRAINT $contraintName");

        // Standardize existing data
        $this->standardizeExistingStatuses();
        $this->standardizeExistingCategories();
        $this->convertMolIdCsvToJson();

        // Change column types to enum
        Schema::table('reports', function (Blueprint $table) {
            // $table->enum('status', ReportStatus::values())->change();
            // $table->enum('report_category', ReportCategory::values())->change();
            DB::statement('ALTER TABLE reports ADD CONSTRAINT check_status CHECK (status IN (\''.implode('\',\'', ReportStatus::values()).'\'))');
            DB::statement('ALTER TABLE reports ADD CONSTRAINT check_report_category CHECK (report_category IN (\''.implode('\',\'', ReportCategory::values()).'\'))');
            // $table->json('mol_ids')->change();
            DB::statement('ALTER TABLE reports ALTER COLUMN mol_id_csv TYPE json USING mol_id_csv::json');
            $table->renameColumn('mol_id_csv', 'mol_ids');
            $table->json('mol_ids')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Remove the check constraints we added
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_status');
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS check_report_category');

        // First rename the column back
        Schema::table('reports', function (Blueprint $table) {
            $table->renameColumn('mol_ids', 'mol_id_csv');
        });

        // Change column types back to original
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status')->change();
            $table->string('mol_id_csv')->change(); // Change from JSON back to string
            $table->string('report_category')->change(); // Change to string first to allow any value
        });

        // Convert JSON arrays back to CSV strings
        $this->convertMolIdsToCSV();

        // Transform categories back to original values
        $this->revertCategoryTransformation();

        // Re-add the original enum constraint for report_category
        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_report_category_check CHECK (report_category::text = ANY (ARRAY['new_molecule'::text, 'report'::text, 'change'::text]))");
    }

    private function standardizeExistingStatuses(): void
    {
        // Map old non-standardized values to new enum values
        // Customize this mapping based on your existing data
        $statusMapping = [
            'submitted' => ReportStatus::SUBMITTED->value,
            'pending' => ReportStatus::SUBMITTED->value,
            'approved' => ReportStatus::APPROVED->value,
            'rejected' => ReportStatus::REJECTED->value,
        ];

        // Get all reports that need updating
        $reports = \App\Models\Report::whereIn('status', array_keys($statusMapping))->get();

        // Update each report individually to trigger auditing
        foreach ($reports as $report) {
            if (isset($statusMapping[$report->status])) {
                $report->status = $statusMapping[$report->status];
                $report->save();
            }
        }
    }

    private function standardizeExistingCategories(): void
    {
        // Map old category values to new enum values
        $categoryMapping = [
            'new_molecule' => ReportCategory::SUBMISSION->value,
            'report' => ReportCategory::REVOKE->value,
            'change' => ReportCategory::UPDATE->value,
        ];

        // Get all reports that need updating
        $reports = \App\Models\Report::whereIn('report_category', array_keys($categoryMapping))->get();

        // Update each report individually to trigger auditing
        foreach ($reports as $report) {
            if (isset($categoryMapping[$report->report_category])) {
                $report->report_category = $categoryMapping[$report->report_category];
                $report->save();
            }
        }
    }

    private function convertMolIdCsvToJson(): void
    {
        // Get all reports that have data in the mol_id_csv column
        $reports = \App\Models\Report::whereNotNull('mol_id_csv')
            ->where('mol_id_csv', '<>', '')
            ->get();

        foreach ($reports as $report) {
            // Convert comma-separated string to array
            $molIdsArray = array_map('trim', explode(',', $report->mol_id_csv));

            // Filter out empty values
            $molIdsArray = array_filter($molIdsArray, function ($value) {
                return $value !== '';
            });

            // Convert to JSON and save
            $report->mol_id_csv = json_encode(array_values($molIdsArray));
            $report->save();
        }

        // For reports with empty mol_id_csv, set to empty JSON array
        \App\Models\Report::whereNull('mol_id_csv')
            ->orWhere('mol_id_csv', '')
            ->update(['mol_id_csv' => json_encode([])]);

        // Log completion
        Log::info('Converted mol_id_csv values to JSON format');
    }

    private function convertMolIdsToCSV(): void
    {
        // Get all reports that have JSON data
        $reports = \App\Models\Report::whereNotNull('mol_id_csv')->get();

        foreach ($reports as $report) {
            try {
                // Decode JSON to array
                $molIdsArray = json_decode($report->mol_id_csv, true);

                // Convert array to comma-separated string
                if (is_array($molIdsArray)) {
                    $report->mol_id_csv = implode(',', $molIdsArray);
                    $report->save();
                }
            } catch (\Exception $e) {
                // Log error but continue with other records
                Log::error("Failed to convert mol_ids JSON to CSV for report ID {$report->id}: ".$e->getMessage());
            }
        }

        Log::info('Converted mol_ids JSON values back to CSV format');
    }

    private function revertCategoryTransformation(): void
    {
        // Reverse mapping from new enum values back to original values
        $reverseMapping = [
            ReportCategory::SUBMISSION->value => 'new_molecule',
            ReportCategory::REVOKE->value => 'report',
            ReportCategory::UPDATE->value => 'change',
        ];

        // Get all reports
        $reports = \App\Models\Report::whereIn('report_category', array_keys($reverseMapping))->get();

        // Update each report individually to trigger auditing
        foreach ($reports as $report) {
            if (isset($reverseMapping[$report->report_category])) {
                $report->report_category = $reverseMapping[$report->report_category];
                $report->save();
            }
        }

        Log::info('Reverted report_category values to original format');
    }
};
