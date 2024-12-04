<?php

namespace App\Livewire;

use Illuminate\Support\Facades\File;
use Livewire\Component;

class AdvancedSearch extends Component
{
    public $schema = [];

    public $searchParams = [];

    public $isLoading = true;

    public function mount()
    {
        $this->loadSchema();
    }

    public function loadSchema()
    {
        $jsonPath = public_path('assets/properties_metadata.json');

        if (! File::exists($jsonPath)) {
            $this->isLoading = false;

            return;
        }

        $jsonContent = File::get($jsonPath);
        $this->schema = json_decode($jsonContent, true) ?? [];

        foreach ($this->schema as $key => $field) {
            switch ($field['type']) {
                case 'range':
                    // Store both 'min' and 'max' in searchParams for range types
                    $this->searchParams[$key] = [
                        'min' => $field['range']['min'],
                        'max' => $field['range']['max'],
                    ];
                    break;

                case 'boolean':
                    // Default to false for boolean fields
                    $this->searchParams[$key] = 'undefined';
                    break;

                case 'select':
                    // Default to an empty array for select fields
                    $this->searchParams[$key] = [];
                    break;
            }
        }

        $this->isLoading = false;
    }

    public function search()
    {
        $filteredParams = array_filter($this->searchParams, fn ($value) => ! empty($value));
        $this->dispatch('search-performed', ['params' => $filteredParams]);
    }

    public function updateSearchParam() {}

    public function render()
    {
        return view('livewire.advanced-search');
    }
}
