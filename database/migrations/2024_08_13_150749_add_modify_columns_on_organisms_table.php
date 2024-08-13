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
        Schema::table('organisms', function (Blueprint $table) {
            $table->string('name')->unique()->change();
            $table->integer('molecule_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisms', function (Blueprint $table) {
            $table->dropUnique('organisms_name_unique');
            $table->dropColumn(['molecule_count']);
        });
    }
};
