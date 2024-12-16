<?php

namespace App\Livewire;

use Cache;
use Illuminate\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class MoleculeDetails extends Component
{
    public $molecule;

    public function mount($molecule)
    {
        $this->molecule = $molecule;
    }

    public function rendered()
    {
        $molecule = $this->molecule;
        $id = $molecule->identifier;
        $_molecule = Cache::get('molecules.'.$id);
        if ($_molecule && ! $_molecule->relationLoaded('properties')) {
            Cache::forget('molecules.'.$id);
            Cache::remember('molecules.'.$id, 172800, function () use ($molecule) {
                $molecule['schema'] = $molecule->getSchema();

                return $molecule;
            });
        }
    }

    public function placeholder()
    {
        return <<<'HTML'
                <div>
                    <div class="relative isolate -z-10">
                        <svg class="absolute inset-x-0 -top-52 -z-10 h-[64rem] w-full stroke-gray-200 [mask-image:radial-gradient(32rem_32rem_at_center,white,transparent)]" aria-hidden="true">
                            <defs>
                            <pattern id="1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84" width="200" height="200" x="50%" y="-1" patternUnits="userSpaceOnUse">
                                <path d="M.5 200V.5H200" fill="none" />
                            </pattern>
                            </defs>
                            <svg x="50%" y="-1" class="overflow-visible fill-gray-50">
                            <path d="M-200 0h201v201h-201Z M600 0h201v201h-201Z M-400 600h201v201h-201Z M200 800h201v201h-201Z" stroke-width="0" />
                            </svg>
                            <rect width="100%" height="100%" stroke-width="0" fill="url(#1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84)" />
                        </svg>
                    </div>
                    <div class="w-full h-screen flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        &nbsp; Loading
                    </div>
                </div>
        HTML;
    }

    public function getReferenceUrls($pivot)
    {
        $references = explode('|', $pivot->reference);
        $urls = explode('|', $pivot->url);
        $combined = array_combine($references, $urls);
        $combined = array_map(function ($key, $value) {
            return [$key => $value];
        }, $references, $urls);

        return $combined;
    }

    public function render(): View
    {
        return view('livewire.molecule-details');
    }
}
