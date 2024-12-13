<?php

namespace App\Livewire;

use Livewire\Component;

class PropertiesPlot extends Component
{
    public $property;

    public $name;

    public $name_corrections = [
        'van_der_walls_volume' => 'Van der Waals volume <span style="font-family: \'Times New Roman\', serif;">
                                                                <span style="font-size: 20px;">(V<span style="font-size: 16px; position: relative;  font-style: italic;">w</span>
                                                                )</span>
                                                                </span>',
        'fractioncsp3' => 'Fraction Csp3',
        'qed_drug_likeliness' => 'QED drug likeness',
        'hydrogen_bond_acceptors_lipinski' => 'Hydrogen bond acceptors Lipinski',
        'hydrogen_bond_donors_lipinski' => 'Hydrogen bond donors Lipinski',
        'lipinski_rule_of_five_violations' => 'Lipinski\'s rule of 5 violations',
    ];

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
