<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

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
    $mol_sdf = $this->molecule->structures->getAttributes()['3d'];
    dd([
        'raw_data' => $mol_sdf,
        'type' => gettype($mol_sdf),
        'is_json' => json_decode($mol_sdf) !== null
    ]);
    
    // Replace line breaks with \n
    $mol = str_replace(["\r\n", "\r", "\n"], "\\n", $mol_sdf);
    // Remove the SDF terminator
    $mol = str_replace("$$$$", "", $mol);
    // Escape quotes
    $mol = str_replace('"', '\"', $mol);

    $mol_template = <<<HTML
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
        </head>
        <body>
            <div id="viewer" style="width: 100%; height: 100vh; margin: 0; padding: 0; border: 0;"></div>
            <script>
                $(document).ready(function() {
                    let molData = "{$mol}";
                    var viewer = \$3Dmol.createViewer("viewer");
                    viewer.setBackgroundColor(0xffffff);
                    viewer.addModel(molData, "mol");
                    viewer.setStyle({stick:{}});
                    viewer.zoomTo();
                    viewer.render();
                });
            </script>
        </body>
        </html>
HTML;

    return $mol_template;
}

    public function downloadMolFile($toolkit)
    {
        $structureData = json_decode($this->molecule->structures->getAttributes()['3d'], true);

        return response()->streamDownload(function () use ($structureData) {
            echo $structureData;
        }, $this->identifier . '.sdf', [
            'Content-Type' => 'chemical/x-mdl-sdfile',
        ]);
    }

    public function render()
    {
        return view('livewire.molecule-depict3d');
    }
}
