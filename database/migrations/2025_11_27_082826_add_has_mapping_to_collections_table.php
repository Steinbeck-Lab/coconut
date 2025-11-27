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
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('has_mapping')->default(false)->nullable();
        });

        DB::table('collections')->whereIn('id', [5, 7, 20, 21, 23, 27, 30, 31, 33, 34, 39, 40, 43, 48, 53, 55, 56, 57, 59, 60, 62, 64, 65, 66, 67, 68, 69, 70])->update(['has_mapping' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('has_mapping');
        });
    }
};
