<?php

namespace App\Livewire;

use Livewire\Component;

class Faqs extends Component
{
    public $faqs = [
        'I am a developer. Can I make links into COCONUT or access COCONUT via API?' => "COCONUT provides RESTful APIs, which can be accessed using any programming language that supports HTTP requests. To get started, you'll need to sign up for a COCONUT account and obtain an API key. Then, you can use the API documentation to understand the available endpoints and parameters, and use the API endpoints to create links to COCONUT or perform video processing operations.",
        'What should I do if I think I\'ve found an error in the data?' => 'If you believe you\'ve found an error in the data within the COCONUT database, you can report it to COCONUT\'s curation team by submitting an update to the COCONUT entry on the compound page or by creating a support ticket.',
        'Can I download the entire COCONUT database?' => 'COCONUT Online offers the different download options of fragments or a complete database with all information included. Download Natural Products Structures in SDF format. The SDF (structure data file) represents a chemical data file format developed by MDL. In this type of format, the natural products are delimited by lines consisting of four dollar signs ($$$$). All associated data items are denoted for every natural product in the database. Download the complete COCONUT dataset as a MongoDB dump. Using this option, all datasets are imported with the same visual depiction as it is at the Website. Download Natural Products Structures in SMILES format',
        'Can I search for a molecule by its structure?' => 'An example of URL link for substructure search: https://COCONUT.naturalproducts.net/api/search/substructure?type=default&max-hits=100&smiles=O=C1OC(C(O)=C1O)CO The user should use the above-presented URL link by adjusting the following options: type default for detecting the substructure with the Ullmann algorithm df for detecting the substructure with depth-first pattern vf for detecting the substructure with Vento-Foggia algorithm max-hits represents the maximum number of natural products to be displayed.',
    ];

    public function render()
    {
        return view('livewire.faqs');
    }
}
