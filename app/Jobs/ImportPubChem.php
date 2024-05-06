<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Http;

class ImportPubChem implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $molecule;

    /**
     * Create a new job instance.
     */
    public function __construct($molecule)
    {
        $this->molecule = $molecule;
    }

    /**
     * Get a unique identifier for the queued job.
     */
    public function uniqueId(): string
    {
        return $this->molecule->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->fetchIUPACNameFromPubChem();
    }

    public function fetchIUPACNameFromPubChem()
    {
        $smilesURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/smiles/cids/TXT?smiles='.urlencode($this->molecule->canonical_smiles);
        $cid = Http::get($smilesURL)->body();
        echo($cid);
        if ($cid && $cid != 0) {
            $cidPropsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.trim(preg_replace('/\s+/', ' ', $cid)).'/json';
            $data = Http::get($cidPropsURL)->json();
            $props = $data['PC_Compounds'][0]['props'];
            $IUPACName = null;
            foreach ($props as $prop) {
                if ($prop['urn']['label'] == 'IUPAC Name' && $prop['urn']['name'] == 'Preferred') {
                    $IUPACName = $prop['value']['sval'];
                }
            }
            $this->fetchSynonymsCASFromPubChem($cid);
            if ($IUPACName) {
                $this->molecule->iupac_name = $IUPACName;
            }
            $this->molecule->save();
        }
    }

    public function fetchSynonymsCASFromPubChem($cid)
    {
        if ($cid && $cid != 0) {
            $synonymsURL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/cid/'.trim(preg_replace('/\s+/', ' ', $cid)).'/synonyms/txt';
            $data = Http::get($synonymsURL)->body();
            $synonyms = preg_split("/\r\n|\n|\r/", $data);
            if ($synonyms && count($synonyms) > 0) {
                if ($synonyms[0] != 'Status: 404') {
                    $pattern = "/\b[1-9][0-9]{1,5}-\d{2}-\d\b/";
                    $casIds = preg_grep($pattern, $synonyms);
                    if($this->molecule->synonyms){
                        $_synonyms = $this->molecule->synonyms;
                        $synonyms[] = $_synonyms;
                        $this->molecule->synonyms = $synonyms;
                    }else{
                        $this->molecule->synonyms = $synonyms;
                    }
                    $this->molecule->cas = array_values($casIds);
                    if(!$this->molecule->name){
                        $this->molecule->name = $synonyms[0];
                    }
                }
            }
        }
    }
}
