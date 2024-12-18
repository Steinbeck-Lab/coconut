<?php

namespace App\Livewire;

use Livewire\Component;

class EntryDetailsDisplay extends Component
{
    public $mol = null;

    public $collection = null;

    public $reference = null;

    public $entry_details = [];

    public function getEntryDetails()
    {
        $this->entry_details = $this->mol->entries()->where('collection_id', $this->collection->id)->where('reference_id', $this->reference)->get();
    }

    public function render()
    {
        return view('livewire.entry-details-display', [
            'entry_details' => $this->entry_details,
        ]);
    }
}
