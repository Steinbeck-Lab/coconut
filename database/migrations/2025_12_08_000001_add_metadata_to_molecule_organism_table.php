<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->jsonb('metadata')->nullable()->after('citation_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('molecule_organism', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
