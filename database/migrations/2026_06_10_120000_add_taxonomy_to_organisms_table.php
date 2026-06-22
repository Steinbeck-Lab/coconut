<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organisms', function (Blueprint $table) {
            $table->json('taxonomy')->nullable()->after('rank');
            $table->timestamp('taxonomy_fetched_at')->nullable()->after('taxonomy');
        });
    }

    public function down(): void
    {
        Schema::table('organisms', function (Blueprint $table) {
            $table->dropColumn(['taxonomy', 'taxonomy_fetched_at']);
        });
    }
};
