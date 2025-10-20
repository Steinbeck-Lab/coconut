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
        // $search = strtolower($this->query ?? '');
        $query = Collection::query()
            ->where('status', 'PUBLISHED')
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(title) ILIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(description) ILIKE ?', ['%'.$search.'%']);
            });

        // When searching, prioritize relevance first, then apply user sorting as secondary
        if (! empty($search)) {
            $query->orderByRaw('title ILIKE ? DESC', [$search.'%'])
                ->orderByRaw('description ILIKE ? DESC', [$search.'%']);

            // Apply user sorting as secondary criteria for items with same relevance
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
        } else {
            // When not searching, apply only user-selected sorting
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
        }

        $collections = $query->paginate($this->size);

        return view('livewire.collection-list', ['collections' => $collections]);
    }
}
