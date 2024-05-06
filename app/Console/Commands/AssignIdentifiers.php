<?php

namespace App\Console\Commands;

use App\Models\Molecule;
use App\Models\Ticker;
use Illuminate\Console\Command;

class AssignIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'molecules:assign-identifiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Molecule::select('id')->chunk(10000, function ($mols) {
            foreach ($mols as $mol) {
                $id = $mol->id;
                echo $id;
                echo "\r\n";
                // fetch molecule
                $molecule = Molecule::whereId($id)->first();
                if ($molecule->identifier == null) {
                    // if molecule has_stereo false
                    if (! $molecule->has_stereo) {
                        $cnID = $this->fetchIdentifier();
                        echo $cnID;
                        echo "\r\n";
                        $molecule->identifier = $cnID;
                        if ($molecule->is_parent) {
                            $variants = $molecule->variants;
                            $i = $molecule->ticker + 1;
                            foreach ($variants as $variant) {
                                $variantID = $cnID.'.'.$i;
                                $variant->identifier = $variantID;
                                echo $variantID;
                                echo "\r\n";
                                $variant->save();
                                $i += 1;
                            }
                            $molecule->ticker = $i;
                        }
                        $molecule->save();
                    }
                }
            }
        });
    }

    public function fetchIdentifier()
    {
        $ticker = Ticker::first();
        $identifier = $ticker->index + 1;
        $ticker->index = $identifier;
        $ticker->save();
        $CNP = 'CNP2_'.$identifier;

        return $CNP;
    }
}
