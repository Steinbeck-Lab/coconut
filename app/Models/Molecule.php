<?php

namespace App\Models;

use App\Observers\MoleculeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\SchemaOrg\Schema;
use Spatie\Tags\HasTags;

#[ObservedBy([MoleculeObserver::class])]
class Molecule extends Model implements Auditable
{
    use HasFactory;
    use HasTags;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'standard_inchi',
        'standard_inchi_key',
        'canonical_smiles',
        'sugar_free_smiles',
        'identifier',
        'name',
        'cas',
        'synonyms',
        'iupac_name',
        'murko_framework',
        'structural_comments',

        'name_trust_level',
        'annotation_level',
        'parent_id',
        'variants_count',

        'active',
        'has_variants',
        'has_stereo',
        'is_tautomer',
        'is_parent',
        'is_placeholder',
        'curation_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'synonyms' => 'array',
        'cas' => 'array',
        'comment' => 'array',
        'curation_status' => 'array',
    ];

    /**
     * Get all of the citations for the collection.
     */
    public function citations(): MorphToMany
    {
        return $this->morphToMany(Citation::class, 'citable');
    }

    /**
     * Get all of the entries for the molecule.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the properties associated with the molecule.
     */
    public function properties(): HasOne
    {
        return $this->hasOne(Properties::class);
    }

    public function structures(): HasOne
    {
        return $this->hasOne(Structure::class);
    }

    /**
     * Get the variants associated with the molecule.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the variants associated with the molecule.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    /**
     * Get the collections associated with the molecule.
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->withPivot('url', 'reference', 'mol_filename', 'structural_comments')->withTimestamps();
    }

    /**
     * Get all of the organisms reported for the molecule.
     */
    public function organisms(): BelongsToMany
    {
        return $this->belongsToMany(Organism::class)
            ->using(MoleculeOrganism::class)
            ->withPivot([
                'collection_ids',
                'citation_ids',
                'metadata',
            ])
            ->withTimestamps();
    }

    /**
     * Get all of the geo-locations for the molecule.
     */
    public function geo_locations(): BelongsToMany
    {
        return $this->belongsToMany(GeoLocation::class, 'geo_location_molecule', 'molecule_id', 'geo_location_id')
            ->withTimestamps();
    }

    /**
     * Get all of the reports for the molecule.
     */
    public function reports(): MorphToMany
    {
        return $this->morphToMany(Report::class, 'reportable');
    }

    /**
     * Get all related molecules.
     */
    public function related()
    {
        return $this->belongsToMany(Molecule::class, 'molecule_related', 'molecule_id', 'related_id')->withTimestamps();
    }

    /**
     * Get the issues associated with the molecule.
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class)->withPivot('is_active', 'is_resolved', 'meta_data')->withTimestamps();
    }

    public function transformAudit(array $data): array
    {
        return changeAudit($data);
    }

    /**
     * Get schema json
     */
    public function getSchema($type = 'bioschemas')
    {
        if ($type === 'bioschemas' && $this->properties) {
            $moleculeSchema = Schema::MolecularEntity();
            $moleculeSchema['@id'] = $this->standard_inchi_key;
            $moleculeSchema['dct:conformsTo'] = $this->prepareConformsTo();

            $moleculeSchema->identifier($this->identifier)
                ->name($this->name)
                ->url(config('app.url').'/compound/'.$this->identifier)
                ->inChI($this->standard_inchi)
                ->inChIKey($this->standard_inchi_key)
                ->iupacName($this->iupac_name)
                ->molecularFormula($this->properties->molecular_formula)
                ->molecularWeight($this->properties->molecular_weight)
                ->monoisotopicMolecularWeight($this->properties->exact_molecular_weight)
                ->smiles($this->canonical_smiles);

            if ($this->synonyms || $this->cas) {
                $alternateNames = $this->synonyms ?? [];
                if ($this->cas) {
                    $alternateNames[] = $this->cas;
                }
                $moleculeSchema->alternateName($alternateNames);
            }

            if ($this->organisms) {
                $organisms = $this->organisms ?? [];
                $names = [];
                foreach ($organisms as $organism) {
                    $name = $organism->name;
                    array_push($names, $name);
                }
                $moleculeSchema->taxonomicRange($names);
            }

            $additionalPropertysSchemas = [];
            $moleculeAdditionalPropertys = [
                'annotation_level' => 'Annotation level',
            ];

            foreach ($moleculeAdditionalPropertys as $key => $value) {
                $propertySchema = Schema::PropertyValue();
                $propertySchema->name($value)
                    ->value($this->$key);
                array_push($additionalPropertysSchemas, $propertySchema);
            }

            $propertyAdditionalPropertys = [
                'total_atom_count' => 'Total atom count',
                'aromatic_rings_count' => 'Aromatic rings count',
                'qed_drug_likeliness' => 'QED drug likeliness',
                'formal_charge' => 'Formal charge',
                'contains_sugar' => 'Contains sugar',
                'np_likeness' => 'NP likeness',
                'chemical_class' => 'Chemical class',
                'chemical_sub_class' => 'Chemical sub class',
                'chemical_super_class' => 'Chemical super class',
            ];

            foreach ($propertyAdditionalPropertys as $key => $value) {
                $propertySchema = Schema::PropertyValue();
                $propertySchema->name($value)
                    ->value($this->properties->$key);
                array_push($additionalPropertysSchemas, $propertySchema);
            }

            if ($this->geo_locations) {
                $geo_locations = $this->geo_locations ?? [];
                $names = [];
                foreach ($geo_locations as $geo_location) {
                    $name = $geo_location->name;
                    array_push($names, $name);
                }
                $propertySchema = Schema::PropertyValue();
                $propertySchema->name('Geographic location')
                    ->value($names);
                array_push($additionalPropertysSchemas, $propertySchema);
            }

            // Use setProperty since MolecularEntity doesn't expose additionalProperty in its contract
            $moleculeSchema->setProperty('additionalProperty', $additionalPropertysSchemas);

            $datasetSchema = Schema::Dataset();
            $datasetSchema->name($this->name);
            $datasetSchema->description('Natural product in the COCONUT database with details of source organisms, geolocations and citations.');
            $datasetSchema->license('https://creativecommons.org/licenses/by/4.0/');

            $catalogs = [];
            if ($this->collections) {
                $collections = $this->collections ?? [];
                foreach ($collections as $collection) {
                    $dataCatalogSchema = Schema::DataCatalog();
                    $dataCatalogSchema->name('Geographic location')
                    // ->license($collection->licenses->url)
                        ->additionalType('collection')
                        ->description($collection->description)
                        ->identifier($collection->identifier)
                        ->url($collection->url)
                        ->name($collection->title);
                    array_push($catalogs, $dataCatalogSchema);
                }
            }

            $datasetSchema->includedInDataCatalog($catalogs)
                ->about($moleculeSchema);

            $citationsSchemas = [];
            if ($this->citations) {
                $citations = $this->citations ?? [];
                foreach ($citations as $citation) {
                    $citationSchema = Schema::CreativeWork();
                    $citationSchema->identifier($citation->doi)
                        ->headline($citation->title)
                        ->author($citation->authors);
                    array_push($citationsSchemas, $citationSchema);
                }
            }
            $datasetSchema->citation($citationsSchemas);

            $contactPoint = Schema::ContactPoint();
            $contactPoint->contactType('research inquiries')
                ->email('info.coconut@uni-jena.de');

            $coconut = Schema::Organization();
            $coconut->url('https://coconut.naturalproducts.net/')
                ->name('COCONUT - COlleCtion of Open Natural prodUcTs')
                ->contactPoint($contactPoint);

            $datasetSchema->creator($coconut);

            return $datasetSchema;
        }
    }

    public function prepareConformsTo()
    {
        $creativeWork = Schema::creativeWork();
        $creativeWork['@id'] = 'https://bioschemas.org/profiles/MolecularEntity/0.5-RELEASE';
        $confromsTo = $creativeWork;

        return $confromsTo;
    }
}
