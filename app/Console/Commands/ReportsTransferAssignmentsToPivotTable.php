<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportsTransferAssignmentsToPivotTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:transfer-to-pivot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transfer comment and assigned_to from reports table to report_user pivot table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting data transfer...');

        $reports = Report::all();

        // pending status fix
        $reports->each(function ($report) {
            if ($report->status == 'pending') {
                $report->status = 'submitted';
                $report->save();
            }
        });

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

        $this->info("Found {$reports->count()} reports with assigned approvers.");

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        $transferred = 0;

        foreach ($reports as $report) {
            // Create new record in pivot table with matching timestamps

            if ($report->status == 'approved' || $report->status == 'rejected') {
                DB::table('report_user')->insert([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to,
                    'comment' => $report->comment,
                    'curator_number' => 1, // Set curator_number to 1
                    'status' => $report->status == 'approved' ? 'pending_approval' : 'pending_rejection', // This is a dummy row as old approved or rejected reports have only one curator
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ]);
                DB::table('report_user')->insert([
                    'report_id' => $report->id,
                    'user_id' => $report->assigned_to,
                    'comment' => $report->comment,
                    'curator_number' => 2, // Set curator_number to 2
                    'status' => $report->status, // Use the status from reports table
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
            $transferred++;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully transferred data for $transferred reports.");
    }
}
