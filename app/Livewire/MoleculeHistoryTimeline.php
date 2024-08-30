<?php

namespace App\Livewire;

use App\Models\Molecule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MoleculeHistoryTimeline extends Component
{
    public $mol = null;

    public $audit_data = [];

    // #[Computed]
    public function getHistory()
    {
        $audit_data = [];
        $molecule = Molecule::find($this->mol->id);
        foreach ($molecule->audits as $index => $audit) {
            // dd($audit->getMetadata());
            // dd(array_keys($audit->getModified())[0]);
            $audit_data[$index]['user_name'] = $audit->getMetadata()['user_name'];
            $audit_data[$index]['event'] = $audit->getMetadata()['audit_event'];
            $audit_data[$index]['created_at'] = date('Y/m/d', strtotime($audit->getMetadata()['audit_created_at']));
            foreach ($audit->getModified() as $key => $value) {
                $audit_data[$index]['affected_column'] = $key;
                $audit_data[$index]['old_value'] = $value['old'];
                $audit_data[$index]['new_value'] = $value['new'];
            }
        }
        $this->audit_data = $audit_data;
    }

    public function render()
    {
        return view('livewire.molecule-history-timeline')->with([
            'audit_data' => $this->audit_data,
        ]);
    }
}
