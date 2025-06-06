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
        Schema::table('molecules', function (Blueprint $table) {
            $table->json('curation_status')->nullable();
        });

        // Update existing molecules with default completed curation status (only for molecules created before 2025)
        DB::table('molecules')
            ->whereNull('curation_status')
            ->where('created_at', '<', '2025-01-01 00:00:00')
            ->update([
            'curation_status' => json_encode([
                'publish-molecules' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ],
                'enrich-molecules' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ],
                'import-pubchem-names' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ],
                'generate-properties' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ],
                'classify' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ],
                'generate-coordinates' => [
                    'status' => 'completed',
                    'error_message' => null,
                    'processed_at' => DB::raw('created_at')
                ]
            ])
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecules', function (Blueprint $table) {
            $table->dropColumn('curation_status');
        });
    }
};
