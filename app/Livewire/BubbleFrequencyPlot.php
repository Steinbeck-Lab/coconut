<?php

namespace App\Livewire;

use Livewire\Component;

class BubbleFrequencyPlot extends Component
{
    public $chartData;

    public $columnName;

    public $chartId;

    public $name_corrections = [
        'np_classifier_class' => 'NPClassifier\'s Chemical Classes <a href="https://pubs.acs.org/doi/10.1021/acs.jnatprod.1c00399" target="_blank" rel="noopener noreferrer"><svg class="inline-block w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg></a>',
        'chemical_class' => 'ClassyFire\'s Chemical Classes <a href="https://jcheminf.biomedcentral.com/articles/10.1186/s13321-016-0174-y" target="_blank" rel="noopener noreferrer"><svg class="inline-block w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg></a>',
    ];

    public function mount($chartName, $chartData)
    {
        // Extract column names
        [$this->columnName] = explode('|', $chartName);

        // Ensure data is properly formatted
        $this->chartData = array_map(function ($item) {
            return [
                'column_values' => $item['column_values'],
                'first_column_count' => $item['first_column_count'] ?? 0,
                'second_column_count' => $item['second_column_count'] ?? 0,
            ];
        }, $chartData);

        $this->chartId = 'bubble-chart-'.uniqid();
    }

    public function render()
    {
        return view('livewire.bubble-frequency-plot');
    }
}
