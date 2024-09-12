<?php

namespace App\Livewire;

use Livewire\Component;

class MoleculeHistoryTimeline extends Component
{
    public $mol = null;

    public $audit_data = [];

    public function getHistory()
    {
        $audit_data = [];
        foreach ($this->mol->audits as $index => $audit) {
            $audit_data[$index]['user_name'] = $audit->getMetadata()['user_name'];
            $audit_data[$index]['event'] = $audit->getMetadata()['audit_event'];
            $audit_data[$index]['created_at'] = date('Y/m/d', strtotime($audit->getMetadata()['audit_created_at']));
            foreach ($audit->getModified() as $key => $value) {
                $audit_data[$index]['affected_columns'][$key]['old_value'] = $value['old'];
                $audit_data[$index]['affected_columns'][$key]['new_value'] = $value['new'];
            }
        }

        $initial_audit = [];
        $initial_audit['user_name'] = null;
        $initial_audit['event'] = null;
        $initial_audit['created_at'] = $this->mol->created_at->format('Y/m/d');
        $initial_audit['affected_columns']['created']['old_value'] = null;
        $initial_audit['affected_columns']['created']['new_value'] = null;

        array_unshift($audit_data, $initial_audit);
        $this->audit_data = $audit_data;
    }

    public function render()
    {
        return view('livewire.molecule-history-timeline')->with([
            'audit_data' => $this->audit_data,
        ]);
    }
}
