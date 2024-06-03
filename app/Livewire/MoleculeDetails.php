<?php

namespace App\Livewire;

use App\Models\Molecule;
use Cache;
use Livewire\Component;

class MoleculeDetails extends Component
{
    public $molecule;

    public function mount($id)
    {
        $this->molecule = Cache::remember('molecules.'.$id, 1440, function () use ($id) {
            return Molecule::with('properties', 'citations', 'collections', 'audits', 'variants', 'organisms', 'geo_locations', 'related')->where('identifier', $id)->first();
        });
    }

    public function render()
    {
        return view('livewire.molecule-details', [
            'molecule' => $this->molecule,
        ])->layout('layouts.guest')
            ->layoutData([
                'title' => $this->molecule->name ? $this->molecule->name : $this->molecule->iupac_name,
                'description' => $this->molecule->description ?? 'Molecule details for '.($this->molecule->name ? $this->molecule->name : $this->molecule->iupac_name),
                'keywords' => 'natural products, '.$this->molecule->name.', '.$this->molecule->iupac_name.', '.implode(',', $this->molecule->synonyms ?? []),
                'author' => $this->molecule->author ?? 'COCONUT Team',
                'ogTitle' => $this->molecule->name ? $this->molecule->name : $this->molecule->iupac_name,
                'ogDescription' => $this->molecule->description ?? 'Molecule details for '.($this->molecule->name ? $this->molecule->name : $this->molecule->iupac_name),
                'ogImage' => env('CM_API').'depict/2D?smiles='.urlencode($this->molecule->canonical_smiles).'&height=200&width=200&toolkit=cdk' ?? asset('img/coconut-og-image.png'),
                'ogSiteName' => 'Coconut 2.0',
            ]);
    }
}
