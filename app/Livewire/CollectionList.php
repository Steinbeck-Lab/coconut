<?php

namespace App\Livewire;

use App\Models\Collection;
use App\Support\CollectionAnnotationScores;
use Illuminate\Database\Eloquent\Builder;
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

    #[Url(as: 'sortBy')]
    public $sortBy = 'release_date';

    #[Url(as: 'dir')]
    public $sortDir = 'desc';

    #[Url()]
    public $page = null;

    public function updatingQuery(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function toggleSortDir(): void
    {
        $this->sortDir = $this->sortDir === 'desc' ? 'asc' : 'desc';
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->query = '';
        $this->resetPage();
    }

    public function render()
    {
        $search = $this->query;
        $query = Collection::query()
            ->published()
            ->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(title) ILIKE ?', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(description) ILIKE ?', ['%'.$search.'%']);
            });

        if (! empty($search)) {
            $query->orderByRaw('title ILIKE ? DESC', [$search.'%'])
                ->orderByRaw('description ILIKE ? DESC', [$search.'%']);
        }

        $this->applySort($query);

        $collections = $query->paginate($this->size);

        return view('livewire.collection-list', [
            'collections' => $collections,
            'sortByOptions' => $this->sortByOptions(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function sortByOptions(): array
    {
        return [
            'release_date' => 'Release date',
            'updated_at' => 'Last updated',
            'created_at' => 'Date added',
            'title' => 'Title',
            'molecules_count' => 'Molecule count',
            'avg_annotation_level' => 'Annotation score',
            'citations_count' => 'Citations',
            'organisms_count' => 'Organisms',
            'geo_count' => 'Geo locations',
        ];
    }

    /**
     * @param  Builder<Collection>  $query
     */
    private function applySort(Builder $query): void
    {
        $dir = $this->normalizedSortDir();

        match ($this->normalizedSortBy()) {
            'title' => $query->orderBy('title', $dir),
            'release_date', 'updated_at', 'created_at',
            'molecules_count', 'citations_count', 'organisms_count', 'geo_count' => $this->orderByColumn($query, $this->normalizedSortBy(), $dir),
            'avg_annotation_level' => CollectionAnnotationScores::applySort($query, $dir),
            default => $this->orderByColumn($query, 'release_date', 'desc'),
        };
    }

    /**
     * @param  Builder<Collection>  $query
     */
    private function orderByColumn(Builder $query, string $column, string $dir): void
    {
        $direction = $dir === 'asc' ? 'ASC' : 'DESC';
        $query->orderByRaw("{$column} {$direction} NULLS LAST");
    }

    private function normalizedSortBy(): string
    {
        return array_key_exists($this->sortBy, $this->sortByOptions())
            ? $this->sortBy
            : 'release_date';
    }

    private function normalizedSortDir(): string
    {
        return in_array($this->sortDir, ['asc', 'desc'], true) ? $this->sortDir : 'desc';
    }
}
