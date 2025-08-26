<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class MoleculeDetails extends Component
{
    public $molecule;

    public $sortedOrganisms;

    public function mount($molecule)
    {
        $this->molecule = $molecule;
        $this->sortOrganisms();
    }

    /**
     * Sort organisms by name and then by taxonomic rank
     */
    private function sortOrganisms()
    {
        if (! $this->molecule->organisms || count($this->molecule->organisms) === 0) {
            $this->sortedOrganisms = collect();

            return;
        }

        // Define the taxonomic rank hierarchy (from highest to lowest)
        $rankHierarchy = [
            'kingdom' => 1,
            'phylum' => 2,
            'class' => 3,
            'subclass' => 4,
            'order' => 5,
            'family' => 6,
            'subfamily' => 7,
            'tribe' => 8,
            'genus' => 9,
            'subgenus' => 10,
            'species' => 11,
            'subspecies' => 12,
            'variety' => 13,
            'sp.' => 14,
            'spec.' => 15,
            'no rank' => 16,
        ];

        // Sort organisms by name first, then by rank
        $this->sortedOrganisms = $this->molecule->organisms->sort(function ($a, $b) use ($rankHierarchy) {
            // First sort by name
            $nameComparison = strcmp(strtolower($a->name), strtolower($b->name));
            if ($nameComparison !== 0) {
                return $nameComparison;
            }

            // If names are equal, sort by rank
            // Remove "(fuzzy)" suffix and convert to lowercase for comparison
            $rankA = strtolower(preg_replace('/\s*\(fuzzy\)$/', '', $a->rank));
            $rankB = strtolower(preg_replace('/\s*\(fuzzy\)$/', '', $b->rank));

            // Get hierarchy values (default to highest value if not found)
            $valueA = $rankHierarchy[$rankA] ?? 999;
            $valueB = $rankHierarchy[$rankB] ?? 999;

            return $valueA - $valueB;
        })->values(); // This resets the array keys to sequential integers starting from 0
    }

    public function rendered()
    {
        $molecule = $this->molecule;
        $id = $molecule->identifier;
        $_molecule = Cache::get('molecules.'.$id);
        if ($_molecule && ! $_molecule->relationLoaded('properties')) {
            Cache::forget('molecules.'.$id);
            Cache::flexible('molecules.'.$id, [172800, 259200], function () use ($molecule) {
                $molecule['schema'] = $molecule->getSchema();

                return $molecule;
            });
        }
    }

    public function placeholder()
    {
        return <<<'HTML'
                <div>
                    <div class="relative isolate -z-10">
                        <svg class="absolute inset-x-0 -top-52 -z-10 h-[64rem] w-full stroke-gray-200 [mask-image:radial-gradient(32rem_32rem_at_center,white,transparent)]" aria-hidden="true">
                            <defs>
                            <pattern id="1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84" width="200" height="200" x="50%" y="-1" patternUnits="userSpaceOnUse">
                                <path d="M.5 200V.5H200" fill="none" />
                            </pattern>
                            </defs>
                            <svg x="50%" y="-1" class="overflow-visible fill-gray-50">
                            <path d="M-200 0h201v201h-201Z M600 0h201v201h-201Z M-400 600h201v201h-201Z M200 800h201v201h-201Z" stroke-width="0" />
                            </svg>
                            <rect width="100%" height="100%" stroke-width="0" fill="url(#1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84)" />
                        </svg>
                    </div>
                    <div class="w-full h-screen flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        &nbsp; Loading
                    </div>
                </div>
        HTML;
    }

    public function getReferenceUrls($pivot)
    {
        if (! $pivot || ! isset($pivot->reference) || ! isset($pivot->url)) {
            return [];
        }
        $references = explode('|', $pivot->reference);
        $urls = explode('|', $pivot->url);
        if (count($references) !== count($urls)) {
            return [];
        }
        $combined = array_combine($references, $urls);
        $combined = array_map(function ($key, $value) {
            return [$key => $value];
        }, $references, $urls);

        return $combined;
    }

    /**
     * Get unique contributors from audit trail
     */
    public function getContributors()
    {
        $audits = collect();

        // Get audits from the molecule
        $audits = $audits->merge($this->molecule->audits);

        // Get audits from related properties
        if ($this->molecule->properties) {
            $audits = $audits->merge($this->molecule->properties->audits);
        }

        // Get audits from related structures
        if ($this->molecule->structures) {
            $audits = $audits->merge($this->molecule->structures->audits);
        }

        // Extract unique users from audits
        $userIds = $audits->pluck('user_id')->unique()->filter();

        $contributors = collect();

        // Check if COCONUT Curator (system user) has contributed
        $hasSystemContributions = $audits->where('user_id', 11)->isNotEmpty() ||
                                 $audits->whereNull('user_id')->isNotEmpty();

        if ($hasSystemContributions) {
            // Add COCONUT Curator as a special contributor
            $contributors->push((object) [
                'id' => 'coconut_curator',
                'name' => 'COCONUT Curator',
                'profile_photo_url' => null,
                'is_system' => true,
            ]);
        }

        // Add actual users (excluding system user ID 11)
        $realUsers = $userIds
            ->filter(function ($userId) {
                return $userId != 11;
            })
            ->map(function ($userId) {
                return User::find($userId);
            })
            ->filter() // Remove null users
            ->map(function ($user) {
                $user->is_system = false;

                return $user;
            });

        $contributors = $contributors->merge($realUsers)->sortBy('name');

        return $contributors;
    }

    /**
     * Get the curation status as an array
     */
    public function getCurationStatusProperty()
    {
        if (! $this->molecule->curation_status) {
            return null;
        }

        return is_string($this->molecule->curation_status)
            ? json_decode($this->molecule->curation_status, true)
            : $this->molecule->curation_status;
    }

    /**
     * Get the list of required curation steps
     */
    public function getRequiredStepsProperty()
    {
        return [
            'publish-molecules',
            'enrich-molecules',
            'import-pubchem-names',
            'generate-properties',
            'classify',
            'generate-coordinates',
        ];
    }

    /**
     * Get the list of incomplete curation steps
     */
    public function getIncompleteStepsProperty()
    {
        $curationStatus = $this->curationStatus;
        $requiredSteps = $this->requiredSteps;
        $incompleteSteps = [];

        if ($curationStatus) {
            foreach ($requiredSteps as $step) {
                if (! isset($curationStatus[$step]) || $curationStatus[$step]['status'] !== 'completed') {
                    $incompleteSteps[] = ucwords(str_replace('-', ' ', $step));
                }
            }
        } else {
            $incompleteSteps = array_map(function ($step) {
                return ucwords(str_replace('-', ' ', $step));
            }, $requiredSteps);
        }

        return $incompleteSteps;
    }

    /**
     * Check if curation is incomplete
     */
    public function getIsCurationIncompleteProperty()
    {
        return ! empty($this->incompleteSteps);
    }

    public function render(): View
    {
        return view('livewire.molecule-details');
    }
}
