<?php

namespace App\Livewire;

use Livewire\Component;

class Faqs extends Component
{
    public $faqs = [
        'I am a developer. Can I make links into COCONUT or access COCONUT via API?' => "COCONUT provides RESTful APIs, which can be accessed using any programming language that supports HTTP requests. To get started, you'll need to sign up for a COCONUT account and obtain an API key. Then, you can use the API documentation to understand the available endpoints and parameters, and use the API endpoints to create links to COCONUT or perform video processing operations.",
        'What should I do if I think I\'ve found an error in the data?' => 'If you believe you\'ve found an error in the data within the COCONUT database, you can report it to COCONUT\'s curation team by submitting an update to the COCONUT entry on the compound page or report using the chat box in the bottom right corner of this page.',
        'Can I download the entire COCONUT database?' => 'COCONUT Online offers the different download options of fragments or a complete database with all information included. All downloads are available under CC0 License. Checkout the download page for the available dowload options.',
        'Can I search for a molecule by its structure?' => 'Yes, you can search using SMILES, InChI or InChIKey. Options are also available to search structures for exact, sub-structure or similarity matches. Please refer to our docs for more details.',
    ];

    public function render()
    {
        return view('livewire.faqs');
    }
}
