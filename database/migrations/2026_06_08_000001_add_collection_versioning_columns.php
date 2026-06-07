<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->foreignId('parent_collection_id')->nullable()->after('license_id')->constrained('collections')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(1)->after('parent_collection_id');
            $table->boolean('is_latest')->default(true)->after('version');
            $table->foreignId('superseded_by_collection_id')->nullable()->after('is_latest')->constrained('collections')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable()->after('superseded_by_collection_id');
            $table->string('version_migration_status', 32)->nullable()->after('superseded_at');
            $table->unsignedInteger('archived_entries_count')->nullable()->after('version_migration_status');
            $table->unsignedInteger('archived_molecules_count')->nullable()->after('archived_entries_count');
            $table->string('doi_base')->nullable()->after('doi');
            $table->string('doi_suffix')->nullable()->after('doi_base');

            $table->unique(['identifier', 'version']);
            $table->index(['parent_collection_id', 'version']);
            $table->index('is_latest');
        });

        Schema::table('entries', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('status');
        });

        Schema::create('collection_version_revocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lineage_root_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('from_collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('to_collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('entry_id')->nullable()->constrained('entries')->nullOnDelete();
            $table->foreignId('molecule_id')->nullable()->constrained('molecules')->nullOnDelete();
            $table->longText('reference_id')->nullable();
            $table->longText('standardized_canonical_smiles')->nullable();
            $table->timestamp('revoked_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index('lineage_root_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE collections DROP CONSTRAINT IF EXISTS collections_status_check');
            DB::statement("ALTER TABLE collections ADD CONSTRAINT collections_status_check CHECK (status::text = ANY (ARRAY['DRAFT'::text, 'REVIEW'::text, 'EMBARGO'::text, 'PUBLISHED'::text, 'REJECTED'::text, 'SUPERSEDED'::text]))");
        }

        DB::table('collections')->update([
            'version' => 1,
            'is_latest' => true,
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE collections DROP CONSTRAINT IF EXISTS collections_status_check');
            DB::statement("ALTER TABLE collections ADD CONSTRAINT collections_status_check CHECK (status::text = ANY (ARRAY['DRAFT'::text, 'REVIEW'::text, 'EMBARGO'::text, 'PUBLISHED'::text, 'REJECTED'::text]))");
        }

        Schema::dropIfExists('collection_version_revocations');

        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropUnique(['identifier', 'version']);
            $table->dropIndex(['parent_collection_id', 'version']);
            $table->dropIndex(['is_latest']);
            $table->dropConstrainedForeignId('parent_collection_id');
            $table->dropConstrainedForeignId('superseded_by_collection_id');
            $table->dropColumn([
                'version',
                'is_latest',
                'superseded_at',
                'version_migration_status',
                'archived_entries_count',
                'archived_molecules_count',
                'doi_base',
                'doi_suffix',
            ]);
        });
    }
};
