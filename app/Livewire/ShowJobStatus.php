<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ShowJobStatus extends Component
{
    public $collection;

    public function mount(?Model $record = null)
    {
        $this->collection = $record;
    }

    public function render()
    {
        return view('livewire.show-job-status', [
            'status' => $this->collection->jobs_status,
            'info' => $this->collection->job_info,
        ]);
    }
}
