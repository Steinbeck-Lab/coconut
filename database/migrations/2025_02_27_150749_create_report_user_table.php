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
        Schema::create('report_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id');
            $table->foreignId('user_id');
            $table->integer('curator_number');
            $table->enum('status', ['pendingApproval', 'pendingRejection', 'approved', 'rejected'])->nullable();
            $table->longText('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_user');
    }
};
