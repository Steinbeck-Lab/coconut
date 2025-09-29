<?php

use App\Enums\ReportStatus;
use App\Models\Report;
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
        Schema::create('report_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id');
            $table->foreignId('user_id');
            $table->integer('curator_number');
            $table->enum('status', [ReportStatus::PENDING_APPROVAL->value, ReportStatus::PENDING_REJECTION->value, ReportStatus::APPROVED->value, ReportStatus::REJECTED->value])->nullable();
            $table->longText('comment')->nullable();
            $table->timestamps();
        });

        $reports = Report::all();

        // fix for reports with is_change set to true but nothing in suggested_changes
        $reports->each(function ($report) {
            if ($report->is_change && empty($report->suggested_changes)) {
                $report->is_change = false;
                $report->save();
            }
        });

        // Get reports that have assigned_to values
        $reports = $reports->filter(function ($report) {
            return $report->assigned_to !== null;
        });

        foreach ($reports as $report) {
            // Create new record in pivot table with matching timestamps

            if ($report->status == ReportStatus::APPROVED->value || $report->status == ReportStatus::REJECTED->value) {
                DB::table('report_user')->insert([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to,
                    'comment' => $report->comment,
                    'curator_number' => 1, // Set curator_number to 1
                    'status' => $report->status == ReportStatus::APPROVED->value ? ReportStatus::PENDING_APPROVAL->value : ReportStatus::PENDING_REJECTION->value, // This is a dummy row as old approved or rejected reports have only one curator
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);
                DB::table('report_user')->insert([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to,
                    'comment' => $report->comment,
                    'curator_number' => 2, // Set curator_number to 2
                    'status' => $report->status == ReportStatus::APPROVED->value ? ReportStatus::APPROVED->value : ReportStatus::REJECTED->value, // Use the status from reports table
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);
            } else {
                DB::table('report_user')->insert([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to,
                    'comment' => $report->comment,
                    'curator_number' => 1, // Set curator_number to 1
                    'status' => null,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the pivot table
        Schema::dropIfExists('report_user');
    }
};
