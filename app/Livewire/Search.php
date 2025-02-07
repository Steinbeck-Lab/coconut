<?php

namespace App\Livewire;

use App\Actions\Coconut\SearchMolecule;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
#[Layout('layouts.guest')]
class Search extends Component
{
    use WithPagination;

    #[Url(except: '', as: 'q')]
    public $query = '';

    #[Url(as: 'limit')]
    public $size = 20;

    #[Url(as: 'sort')]
    public $sort = null;

    #[Url(as: 'page')]
    public $page = null;

    #[Url(as: 'type')]
    public $type = null;

    #[Url(as: 'tagType')]
    public $tagType = null;

    public $collection = null;

    public $organisms = null;

    #[Url(as: 'activeTab')]
    public $activeTab = 'molecules';

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
                        &nbsp; Searching...
                    </div>
                </div>
        HTML;
    }

    public function updatingPage($page)
    {
        $this->page = $page;
    }

    public function updatedQuery()
    {
        $this->page = 1;
        $this->type = null;
        $this->tagType = null;
    }

    public function search(SearchMolecule $search)
    {
        $this->render($search);
    }

    public function render(SearchMolecule $search)
    {
        try {
            $cacheKey = 'search.'.md5($this->query.$this->size.$this->type.$this->sort.$this->tagType.$this->page);

            $results = Cache::remember($cacheKey, now()->addDay(), function () use ($search) {
                return $search->query($this->query, $this->size, $this->type, $this->sort, $this->tagType, $this->page);
            });

            $this->collection = $results[1];
            $this->organisms = $results[2];

            return view('livewire.search', [
                'molecules' => $results[0],
            ]);
        } catch (QueryException $exception) {
            $message = $exception->getMessage();
            if (str_contains(strtolower($message), strtolower('SQLSTATE[42P01]'))) {
                return response()->json(
                    [
                        'message' => 'It appears that the molecules table is not indexed. To enable search, please index molecules table and generate corresponding fingerprints.',
                    ],
                    500
                );
            }

            return response()->json(
                [
                    'message' => $message,
                ],
                500
            );
        }
    }
}
