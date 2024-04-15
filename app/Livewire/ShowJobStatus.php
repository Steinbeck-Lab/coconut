<?php

namespace App\Livewire;

use Livewire\Component;

class ShowJobStatus extends Component
{
    public $collection = null;

    public function mount($record = null)
    {
        $this->collection = $record;
    }

    public function render()
    {
        return view('livewire.show-job-status', [
            'status' => $this->collection ? $this->collection->jobs_status : null,
            'info' => $this->collection ? $this->collection->job_info : null,
        ]);
    }
}
