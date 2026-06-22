<?php

namespace Tests\Unit;

use App\Models\Organism;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrganismReportChangeFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_name_for_report_change_allows_unlinked_existing_organism_name(): void
    {
        Organism::create(['name' => 'Escherichia coli', 'slug' => 'escherichia-coli']);

        $this->assertNull(
            Organism::validateNameForReportChange('Escherichia coli', ['Homo sapiens'])
        );
    }

    public function test_validate_name_for_report_change_rejects_already_linked_organism_name(): void
    {
        $message = Organism::validateNameForReportChange('Homo sapiens', ['Homo sapiens']);

        $this->assertSame('This organism is already linked to this molecule.', $message);
    }

    public function test_report_change_name_rules_allow_existing_database_organism_name(): void
    {
        Organism::create(['name' => 'Escherichia coli', 'slug' => 'escherichia-coli']);

        $rules = ['name' => Organism::reportChangeNameRules(['Homo sapiens'])];

        $validator = Validator::make(['name' => 'Escherichia coli'], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_report_change_name_rules_reject_already_linked_organism_name(): void
    {
        $rules = ['name' => Organism::reportChangeNameRules(['Homo sapiens'])];

        $validator = Validator::make(['name' => 'Homo sapiens'], $rules);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'This organism is already linked to this molecule.',
            $validator->errors()->first('name')
        );
    }

    public function test_default_get_form_still_requires_unique_organism_name(): void
    {
        Organism::create(['name' => 'Escherichia coli', 'slug' => 'escherichia-coli']);

        $validator = Validator::make(
            ['name' => 'Escherichia coli'],
            ['name' => ['required', 'unique:organisms,name']]
        );

        $this->assertTrue($validator->fails());
    }
}
