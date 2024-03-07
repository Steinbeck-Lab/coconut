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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('molecule_id')->unique();
            $table->foreign('molecule_id')->references('id')->on('molecules');

            $table->integer('total_atom_count')->default(0)->nullable();
            $table->integer('heavy_atom_count')->default(0)->nullable();

            $table->float('molecular_weight')->default(0)->nullable();
            $table->float('exact_molecular_weight')->default(0)->nullable();

            $table->longText('molecular_formula')->nullable();

            $table->decimal('alogp')->default(0)->nullable();
            $table->decimal('topological_polar_surface_area')->default(0)->nullable();

            $table->integer('rotatable_bond_count')->default(0)->nullable();

            $table->integer('hydrogen_bond_acceptors')->default(0)->nullable();
            $table->integer('hydrogen_bond_donors')->default(0)->nullable();

            $table->integer('hydrogen_bond_acceptors_lipinski')->default(0)->nullable();
            $table->integer('hydrogen_bond_donors_lipinski')->default(0)->nullable();
            $table->integer('lipinski_rule_of_five_violations')->nullable();

            $table->integer('aromatic_rings_count')->default(0)->nullable();
            $table->decimal('qed_drug_likeliness')->default(0)->nullable();

            $table->integer('formal_charge')->default(0)->nullable();
            $table->decimal('fractioncsp3')->default(0)->nullable();
            $table->integer('number_of_minimal_rings')->nullable();
            $table->decimal('van_der_walls_volume')->nullable();

            $table->boolean('contains_sugar')->nullable();
            $table->boolean('contains_ring_sugars')->nullable();
            $table->boolean('contains_linear_sugars')->nullable();

            $table->jsonb('fragments')->nullable();
            $table->jsonb('fragments_with_sugar')->nullable();

            $table->longText('murko_framework')->nullable();
            $table->decimal('np_likeness')->nullable();

            $table->longText('chemical_class')->nullable();
            $table->longText('chemical_sub_class')->nullable();
            $table->longText('chemical_super_class')->nullable();
            $table->longText('direct_parent_classification')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
