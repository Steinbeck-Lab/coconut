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
    public $sort = 'created_at';

    #[Url()]
    public $page = null;

    public function updatingQuery()
    {
        $this->resetPage();
    }

    public function updatingSort()
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = $this->query;
        $query = Collection::query()
            ->where('status', 'PUBLISHED')
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
            });

        // Apply search-based ordering only when there's a search query
        if (! empty($search)) {
            $query->orderByRaw('title LIKE ? DESC', [$search.'%'])
                ->orderByRaw('description LIKE ? DESC', [$search.'%']);
        }

        // Apply user-selected sorting
        switch ($this->sort) {
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('title', 'asc');
                break;
        }

        $collections = $query->paginate($this->size);

        return view('livewire.collection-list', ['collections' => $collections]);
    }
}
