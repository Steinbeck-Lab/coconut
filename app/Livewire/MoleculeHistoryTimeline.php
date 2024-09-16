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
            // foreach ($audit->getModified() as $key => $value) {
            //     $audit_data[$index]['affected_columns'][$key]['old_value'] = $value['old']??null;
            //     $audit_data[$index]['affected_columns'][$key]['new_value'] = $value['new']??null;
            // }
            $old_key = explode('.', array_keys($audit->old_values)[0])[0];
            $new_key = explode('.', array_keys($audit->new_values)[0])[0];

            $audit_data[$index]['affected_columns'][$old_key]['old_value'] = implode(', ', array_values($audit->old_values));
            $audit_data[$index]['affected_columns'][$new_key]['new_value'] = implode(', ', array_values($audit->new_values));
        }

        $initial_audit = [];
        $initial_audit['user_name'] = null;
        $initial_audit['event'] = null;
        $initial_audit['created_at'] = $this->mol->created_at->format('Y/m/d');
        $initial_audit['affected_columns']['created']['old_value'] = null;
        $initial_audit['affected_columns']['created']['new_value'] = null;

        array_push($audit_data, $initial_audit);
        $this->audit_data = $audit_data;
    }

    public function render()
    {
        return view('livewire.molecule-history-timeline')->with([
            'audit_data' => $this->audit_data,
        ]);
    }
}
