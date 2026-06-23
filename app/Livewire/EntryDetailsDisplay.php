<?php

namespace App\Livewire;

use App\Services\EntryFieldFormatter;
use Livewire\Component;

class EntryDetailsDisplay extends Component
{
    public $mol = null;

    public $collection = null;

    public $reference = null;

    public $entry_details = [];

    public function getEntryDetails(): void
    {
        $entries = $this->mol->entries()
            ->where('collection_id', $this->collection->id)
            ->where('reference_id', $this->reference)
            ->get();

        $this->entry_details = $entries->map(function ($entry) {
            return [
                'entry' => $entry,
                'doi' => EntryFieldFormatter::format($entry->doi),
                'link' => EntryFieldFormatter::format($entry->link),
                'organism' => EntryFieldFormatter::format($entry->organism),
                'organism_part' => EntryFieldFormatter::format($entry->organism_part),
                'geo_location' => EntryFieldFormatter::format($entry->geo_location),
                'location' => EntryFieldFormatter::formatLocation($entry->location),
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.entry-details-display', [
            'entry_details' => $this->entry_details,
        ]);
    }
}
