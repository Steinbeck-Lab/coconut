<?php

namespace App\Livewire;

use Livewire\Component;

class MoleculeHistoryTimeline extends Component
{
    public $mol = null;

    public $audit_data = [];

    /**
     * Format coordinate data for display
     */
    private function formatCoordinateData(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Clean up the coordinate data
        $value = trim($value);
        // Remove any multiple spaces
        $value = preg_replace('/\s+/', ' ', $value);
        // Ensure consistent line endings
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return $value;
    }

    public function getHistory()
    {
        $audit_data = [];
        $audits_collection = $this->mol->audits->merge($this->mol->properties()->get()[0]->audits);

        if ($this->mol->structures()->get()->count() > 0) {
            $audits_collection = $audits_collection->merge($this->mol->structures()->get()[0]->audits);
        }

        foreach ($audits_collection->sortByDesc('created_at') as $index => $audit) {
            $audit_data[$index]['user_name'] = $audit->getMetadata()['user_name'];
            $audit_data[$index]['event'] = $audit->getMetadata()['audit_event'];
            $audit_data[$index]['created_at'] = date('Y/m/d', strtotime($audit->getMetadata()['audit_created_at']));

            $values = ! empty($audit->old_values) ? $audit->old_values : $audit->new_values;
            $first_affected_column = ! empty($values) ? array_keys($values)[0] : null;

            if (str_contains($first_affected_column, '.')) {
                $affected_column = explode('.', $first_affected_column)[0];

                $audit_data[$index]['affected_columns'][$affected_column]['old_value'] = $audit->old_values ? array_values($audit->old_values)[0] : null;
                $audit_data[$index]['affected_columns'][$affected_column]['new_value'] = $audit->new_values ? array_values($audit->new_values)[0] : null;
            } else {
                foreach ($audit->getModified() as $affected_column => $value) {
                    if ($audit['auditable_type'] == "App\Models\Structure" && ($affected_column == 'molecule_id' || $affected_column == 'id')) {
                        continue;
                    }

                    // Handle different column types
                    switch ($affected_column) {
                        case 'iupac_name':
                            $old_value = array_key_exists('old', $value) ? convert_italics_notation($value['old']) : null;
                            $new_value = array_key_exists('new', $value) ? convert_italics_notation($value['new']) : null;
                            break;

                        case '2d':
                        case '3d':
                            $old_value = array_key_exists('old', $value) ? $this->formatCoordinateData($value['old']) : null;
                            $new_value = array_key_exists('new', $value) ? $this->formatCoordinateData($value['new']) : null;
                            break;

                        default:
                            $old_value = array_key_exists('old', $value) ? $value['old'] : null;
                            $new_value = array_key_exists('new', $value) ? $value['new'] : null;
                    }

                    $audit_data[$index]['affected_columns'][$affected_column] = [
                        'old_value' => $old_value,
                        'new_value' => $new_value,
                    ];
                }
            }
        }

        // Add initial audit entry
        $initial_audit = [
            'user_name' => null,
            'event' => null,
            'created_at' => $this->mol->created_at->format('Y/m/d'),
            'affected_columns' => [
                'created' => [
                    'old_value' => null,
                    'new_value' => null,
                ],
            ],
        ];

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
