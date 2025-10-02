<?php

namespace App\Console\Commands\CisTransFix;

use App\Models\Entry;
use Illuminate\Console\Command;

class UpdateCisTransFlags extends Command
{
    protected $signature = 'coconut:update-cis-trans-flags {filename}';

    protected $description = 'Update is_cis_trans flags in entries table from CSV file in storage';

    public function handle()
    {
        $filename = $this->argument('filename');
        $filePath = storage_path($filename);

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        $this->info("Processing: {$filePath}");

        // Get total lines for progress bar
        $totalLines = $this->countLines($filePath) - 1; // Subtract header

        if ($totalLines <= 0) {
            $this->error('No data rows found in CSV file');

            return Command::FAILURE;
        }

        $progressBar = $this->output->createProgressBar($totalLines);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %memory:6s%');

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle); // Skip header

        // Validate header
        $idIndex = array_search('id', $header);
        $cisTransIndex = array_search('cis/trans', $header);

        if ($idIndex === false || $cisTransIndex === false) {
            $this->error("CSV must contain 'id' and 'cis/trans' columns");

            return Command::FAILURE;
        }

        $updateMap = [];
        $batchSize = 10000;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $entryId = (int) $row[$idIndex];
            $value = strtolower(trim($row[$cisTransIndex]));

            if ($value === 'true') {
                $updateMap[$entryId] = true;
            } elseif ($value === 'false') {
                $updateMap[$entryId] = false;
            } else {
                $skipped++;
            }

            // Process batch
            if (count($updateMap) >= $batchSize) {
                $updated += $this->processBatch($updateMap);
                $updateMap = [];
            }

            $progressBar->advance();
        }

        // Process remaining batch
        if (! empty($updateMap)) {
            $updated += $this->processBatch($updateMap);
        }

        fclose($handle);
        $progressBar->finish();

        $this->newLine();
        $this->info("Updated: {$updated} | Skipped: {$skipped}");

        return Command::SUCCESS;
    }

    private function countLines($filePath): int
    {
        $lines = 0;
        $handle = fopen($filePath, 'r');
        while (fgets($handle) !== false) {
            $lines++;
        }
        fclose($handle);

        return $lines;
    }

    private function processBatch(array $updateMap): int
    {
        if (empty($updateMap)) {
            return 0;
        }

        $ids = array_keys($updateMap);
        $existingIds = Entry::whereIn('id', $ids)->pluck('id')->toArray();

        if (empty($existingIds)) {
            return 0;
        }

        // Group IDs by their target value for efficient bulk updates
        $trueIds = [];
        $falseIds = [];

        foreach ($existingIds as $id) {
            if ($updateMap[$id] === true) {
                $trueIds[] = $id;
            } else {
                $falseIds[] = $id;
            }
        }

        $updated = 0;

        // Bulk update all entries that should be true
        if (! empty($trueIds)) {
            $updated += Entry::whereIn('id', $trueIds)->update(['is_cis_trans' => true]);
        }

        // Bulk update all entries that should be false
        if (! empty($falseIds)) {
            $updated += Entry::whereIn('id', $falseIds)->update(['is_cis_trans' => false]);
        }

        return $updated;
    }
}
