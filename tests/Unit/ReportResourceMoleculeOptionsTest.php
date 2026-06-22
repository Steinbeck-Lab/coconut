<?php

namespace Tests\Unit;

use App\Filament\Dashboard\Resources\ReportResource;
use App\Models\Molecule;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ReportResourceMoleculeOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function callStatic(string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod(ReportResource::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null, ...$args);
    }

    public function test_value_keyed_options_uses_value_as_key_and_label(): void
    {
        $options = $this->callStatic('valueKeyedOptions', ['methane', 'CH4']);

        $this->assertSame(['methane' => 'methane', 'CH4' => 'CH4'], $options);
    }

    public function test_value_keyed_options_filters_empty_values(): void
    {
        $options = $this->callStatic('valueKeyedOptions', ['methane', '', null]);

        $this->assertSame(['methane' => 'methane'], $options);
    }

    public function test_value_keyed_options_deduplicates_values(): void
    {
        $options = $this->callStatic('valueKeyedOptions', ['methane', 'methane']);

        $this->assertSame(['methane' => 'methane'], $options);
    }

    public function test_prefilled_select_state_matches_option_keys(): void
    {
        $synonyms = ['methane', 'CH4'];
        $cas = ['74-82-8'];

        $synonymOptions = $this->callStatic('valueKeyedOptions', $synonyms);
        $casOptions = $this->callStatic('valueKeyedOptions', $cas);

        $this->assertEmpty(array_diff($synonyms, array_keys($synonymOptions)));
        $this->assertEmpty(array_diff($cas, array_keys($casOptions)));
    }

    public function test_resolve_molecule_from_report_mol_ids(): void
    {
        $molecule = Molecule::create([
            'identifier' => 'CNP0520383.0',
            'name' => 'old name',
            'synonyms' => ['CH4'],
            'cas' => ['74-82-8'],
        ]);

        $report = new Report([
            'mol_ids' => ['CNP0520383.0'],
            'title' => 'test',
        ]);

        $resolved = $this->callStatic('resolveMolecule', $report, null);

        $this->assertTrue($resolved->is($molecule));
    }

    public function test_resolve_molecule_from_request_compound_id(): void
    {
        $molecule = Molecule::create([
            'identifier' => 'CNP0520383.0',
            'name' => 'methane',
        ]);

        request()->merge(['compound_id' => 'CNP0520383.0']);

        $resolved = $this->callStatic('resolveMolecule', null, null);

        $this->assertTrue($resolved->is($molecule));
    }
}
