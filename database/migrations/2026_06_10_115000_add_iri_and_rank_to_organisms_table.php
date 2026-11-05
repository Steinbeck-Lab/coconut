<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('organisms', 'iri')) {
            Schema::table('organisms', function (Blueprint $table) {
                if (Schema::hasColumn('organisms', 'ontology')) {
                    $table->string('iri')->nullable()->after('name');
                } else {
                    $table->string('iri')->nullable()->after('name');
                }
            });

            if (Schema::hasColumn('organisms', 'ontology')) {
                DB::table('organisms')
                    ->whereNotNull('ontology')
                    ->update(['iri' => DB::raw('ontology')]);
            }
        }

        if (! Schema::hasColumn('organisms', 'rank')) {
            Schema::table('organisms', function (Blueprint $table) {
                $table->string('rank')->nullable()->after('iri');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('organisms', 'rank')) {
            Schema::table('organisms', function (Blueprint $table) {
                $table->dropColumn('rank');
            });
        }

        if (Schema::hasColumn('organisms', 'iri') && ! Schema::hasColumn('organisms', 'ontology')) {
            Schema::table('organisms', function (Blueprint $table) {
                $table->dropColumn('iri');
            });
        }
    }
};
