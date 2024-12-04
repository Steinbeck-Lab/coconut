<?php

namespace App\Observers;

use App\Models\Molecule;
use Illuminate\Support\Facades\Cache;

class MoleculeObserver
{
    /**
     * Handle the Molecule "updated" event.
     */
    public function updated(Molecule $molecule): void
    {
        Cache::forget("molecules.{$molecule->identifier}");
    }
}
