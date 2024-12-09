<?php

namespace App\Livewire;

use Livewire\Component;

class PropertiesPlot extends Component
{
    public $property;

    public $name;

    public function mount($property, $name)
    {
        $this->property = $property;
        $this->name = $name;
    }

    public function render()
    {
        return view('livewire.properties-plot');
    }
}
