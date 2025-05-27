<?php

namespace App\Livewire;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.guest')]
class CollectionList extends Component
{
    use WithPagination;

    #[Url(except: '', keep: true, history: true, as: 'q')]
    public $query = '';

    #[Url(as: 'limit')]
    public $size = 20;

    #[Url()]
    public $sort = null;

    #[Url()]
    public $page = null;

    public function updatingQuery()
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = $this->query;
        $collections = Collection::query()
            ->where('status', 'PUBLISHED')
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
            })
            ->orderByRaw('title LIKE ? DESC', [$search.'%'])
            ->orderByRaw('description LIKE ? DESC', [$search.'%'])
            ->paginate($this->size);

        return view('livewire.collection-list', ['collections' => $collections]);
    }
}
