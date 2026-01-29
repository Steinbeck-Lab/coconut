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
        Schema::table('geo_locations', function (Blueprint $table) {
            // Country information
            $table->string('country')->nullable()->after('name');
            $table->string('country_code', 3)->nullable()->after('country')->comment('ISO 3166-1 alpha-2 or alpha-3 country code');
            $table->string('county')->nullable()->after('country_code')->comment('County/Province/State');

            // Flag emoji or image
            $table->string('flag', 10)->nullable()->after('county')->comment('Country flag emoji (e.g., 🇺🇸) or flag image URL');

            // Geographic coordinates
            // Using separate lat/long columns is better than comma-separated string for queries
            $table->decimal('latitude', 10, 8)->nullable()->after('flag')->comment('Latitude coordinate');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Longitude coordinate');

            // Bounding box as JSON
            $table->json('boundary')->nullable()->after('longitude')->comment('Array of lat/long coordinates forming a bounding box or polygon');

            // Indexes for better query performance
            $table->index('country_code');
            $table->index(['latitude', 'longitude'], 'geo_coordinates_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geo_locations', function (Blueprint $table) {
            $table->dropIndex('geo_coordinates_index');
            $table->dropIndex(['country_code']);

            $table->dropColumn([
                'country',
                'country_code',
                'county',
                'flag',
                'latitude',
                'longitude',
                'boundary',
            ]);
        });
    }
};
