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
        $audits_collection = $this->mol->audits->merge($this->mol->properties()->get()[0]->audits);
        foreach ($audits_collection->sortByDesc('created_at') as $index => $audit) {
            $audit_data[$index]['user_name'] = $audit->getMetadata()['user_name'];
            $audit_data[$index]['event'] = $audit->getMetadata()['audit_event'];
            $audit_data[$index]['created_at'] = date('Y/m/d', strtotime($audit->getMetadata()['audit_created_at']));

            if (str_contains('.', array_keys($audit->old_values)[0])) {
                $old_key = $audit->old_values ? explode('.', array_keys($audit->old_values)[0])[0] : null;
                $new_key = $audit->new_values ? explode('.', array_keys($audit->new_values)[0])[0] : null;

                $old_key = $old_key ?: $new_key;
                $new_key = $new_key ?: $old_key;
            } else {
                foreach ($audit->getModified() as $key => $value) {
                    $audit_data[$index]['affected_columns'][$key]['old_value'] = $value['old'] ?: null;
                    $audit_data[$index]['affected_columns'][$key]['new_value'] = $value['new'] ?: null;
                }
            }
        }

        $initial_audit = [];
        $initial_audit['user_name'] = null;
        $initial_audit['event'] = null;
        $initial_audit['created_at'] = $this->mol->created_at->format('Y/m/d');
        $initial_audit['affected_columns']['created']['old_value'] = null;
        $initial_audit['affected_columns']['created']['new_value'] = null;

        array_push($audit_data, $initial_audit);
        $this->audit_data = $audit_data;
        // dd($audit_data);
    }

    public function render()
    {
        return view('livewire.molecule-history-timeline')->with([
            'audit_data' => $this->audit_data,
        ]);
    }
}
