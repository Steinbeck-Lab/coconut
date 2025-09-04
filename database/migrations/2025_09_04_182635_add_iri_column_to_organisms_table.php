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
            $table->string('iri')->nullable();
            $table->string('rank')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisms', function (Blueprint $table) {
            $table->dropColumn(['iri', 'rank']);
        });
    }
};
