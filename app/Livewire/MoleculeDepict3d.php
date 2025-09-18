<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class MoleculeDepict3d extends Component
{
    public $molecule = null;

    public $smiles = null;

    public $height = 200;

    public $width = '100%';

    public $CIP = true;

    #[Computed]
    public function source()
    {
        // Get the 3D structure data from the database which is stored as JSON
        $modelData = $this->molecule->structures->getAttributes()['3d'];

        // Create HTML for the 3D viewer with the model data
        $html = <<<HTML
                        <!DOCTYPE html>
                        <html>
                        <head>
                        <title>3D Molecule Viewer</title>
                        <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/3Dmol/2.0.1/3Dmol.js"></script>
                        <style>
                        head, body {
                            margin: 0;
                            border: 0;
                            padding: 0;
                            max-height: 100vh
                        }
                        </style>
                        <script>
                        $(document).ready(function() {
                            var viewer = \$3Dmol.createViewer("viewer");
                            viewer.setBackgroundColor(0xffffff);
                            
                            // Parse the JSON-encoded model data
                            var modelData = {$modelData};
                            
                            viewer.addModel(modelData, "mol");
                            viewer.setStyle({stick:{}});
                            viewer.zoomTo();
                            viewer.render();
                        });
                        </script>
                        <body>
                            <div id="viewer" style="width: 100%; height: 100vh; margin: 0; padding: 0; border: 0;"></div>
                        </body>
                        </html>
                    HTML;

        // Base64 encode to avoid issues with special characters
        return 'data:text/html;base64,'.base64_encode($html);
    }

    public function downloadSDFFile()
    {
        $structureData = json_decode($this->molecule->structures->getAttributes()['3d'], true);

        return response()->streamDownload(function () use ($structureData) {
            echo $structureData;
        }, $this->molecule->identifier.'.sdf', [
            'Content-Type' => 'chemical/x-mdl-sdfile',
        ]);
    }

    public function render()
    {
        return view('livewire.molecule-depict3d');
    }
}
