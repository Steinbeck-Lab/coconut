<?php

namespace Tests\Unit;

use App\Models\Organism;
use App\Services\OrganismTaxonomy\NcbiTaxonomyNameResolver;
use App\Services\OrganismTaxonomy\OrganismCurationResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganismCurationResolverTest extends TestCase
{
    private OrganismCurationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new OrganismCurationResolver;
    }

    #[DataProvider('classificationProvider')]
    public function test_classify_detects_curation_patterns(
        string $name,
        ?string $iri,
        string $expectedPattern,
    ): void {
        $organism = new Organism([
            'name' => $name,
            'iri' => $iri,
            'taxonomy' => null,
        ]);

        $this->assertSame($expectedPattern, $this->resolver->classify($organism)->pattern);
    }

    public static function classificationProvider(): array
    {
        return [
            'species placeholder' => ['Streptomyces sp.', 'http://purl.obolibrary.org/obo/NCBITaxon_1931', OrganismCurationResolver::PATTERN_SPECIES_PLACEHOLDER],
            'vernacular dictionary' => ['tomato', null, OrganismCurationResolver::PATTERN_VERNACULAR],
            'infraspecific' => ['Pueraria montana var. lobata', null, OrganismCurationResolver::PATTERN_INFRASPECIFIC],
            'hybrid notation' => ['Citrus × deliciosa', null, OrganismCurationResolver::PATTERN_HYBRID_NOTATION],
            'iri without taxonomy' => ['Aspergillus fumigatus', 'http://purl.obolibrary.org/obo/NCBITaxon_746128', OrganismCurationResolver::PATTERN_IRI_WITHOUT_TAXONOMY],
            'undetermined' => ['Unknown-fungus sp.', null, OrganismCurationResolver::PATTERN_UNDETERMINED],
        ];
    }

    public function test_lookup_candidates_include_genus_fallback_for_species_placeholder(): void
    {
        $organism = new Organism(['name' => 'Penicillium sp.']);

        $candidates = $this->resolver->lookupCandidates($organism);

        $this->assertSame('Penicillium sp.', $candidates[0]);
        $this->assertContains('Penicillium', $candidates);
    }

    public function test_fallback_lookup_candidates_skip_primary_name(): void
    {
        $organism = new Organism(['name' => 'tomato']);

        $fallback = $this->resolver->fallbackLookupCandidates($organism);

        $this->assertNotContains('tomato', $fallback);
        $this->assertContains('Solanum lycopersicum', $fallback);
    }

    public function test_lookup_candidates_map_vernacular_names_to_scientific_names(): void
    {
        $organism = new Organism(['name' => 'potato']);

        $candidates = $this->resolver->lookupCandidates($organism);

        $this->assertContains('Solanum tuberosum', $candidates);
    }

    public function test_lookup_candidates_strip_hybrid_and_infraspecific_markers(): void
    {
        $organism = new Organism(['name' => 'Eucalyptus globulus subsp. bicostata']);

        $candidates = $this->resolver->lookupCandidates($organism);

        $this->assertContains('Eucalyptus globulus subsp. bicostata', $candidates);
        $this->assertContains('Eucalyptus globulus', $candidates);
    }

    public function test_annotate_resolved_taxonomy_records_curation_metadata(): void
    {
        $organism = new Organism(['name' => 'tomato']);
        $classification = $this->resolver->classify($organism);

        $annotated = $this->resolver->annotateResolvedTaxonomy(
            ['canonical_name' => 'Solanum lycopersicum', 'match_type' => 'Exact'],
            $organism,
            'Solanum lycopersicum',
            $classification,
        );

        $this->assertSame('tomato', $annotated['curation']['original_name']);
        $this->assertSame('Solanum lycopersicum', $annotated['curation']['resolved_lookup']);
        $this->assertSame(OrganismCurationResolver::PATTERN_VERNACULAR, $annotated['curation']['pattern']);
    }

    public function test_lookup_candidates_include_ncbi_name_from_iri(): void
    {
        $ncbi = $this->createMock(NcbiTaxonomyNameResolver::class);
        $ncbi->method('scientificNameFromIri')->willReturn('Lindera');

        $resolver = new OrganismCurationResolver(ncbiNameResolver: $ncbi);

        $organism = new Organism([
            'name' => 'Lindera strichnifolia',
            'iri' => 'http://purl.obolibrary.org/obo/NCBITaxon_55957',
        ]);

        $this->assertContains('Lindera', $resolver->lookupCandidates($organism));
    }

    public function test_lookup_candidates_strip_strain_suffix_from_species_placeholder(): void
    {
        $organism = new Organism(['name' => 'Bacillus sp. SNA-60-367']);

        $candidates = $this->resolver->lookupCandidates($organism);

        $this->assertContains('Bacillus', $candidates);
    }

    public function test_undetermined_names_are_not_fixable(): void
    {
        $organism = new Organism(['name' => 'Unknown-bacterium sp.']);

        $this->assertFalse($this->resolver->isFixable($organism));
    }
}
