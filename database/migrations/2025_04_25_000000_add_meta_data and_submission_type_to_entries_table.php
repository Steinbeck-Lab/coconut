<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->enum('submission_type', ['json', 'csv'])->nullable()->after('name');
            $table->json('meta_data')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['submission_type', 'meta_data']);
        });
    }
};
