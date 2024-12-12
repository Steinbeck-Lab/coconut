<?php

namespace App\Livewire;

use Livewire\Component;

class BubbleFrequencyPlot extends Component
{
    public $chartData;

    public $firstColumnName;

    public $secondColumnName;

    public $chartId;

    public $name_corrections = [

    ];

    public function mount($chartName, $chartData)
    {
        // Extract column names
        [$this->firstColumnName, $this->secondColumnName] = explode('|', $chartName);

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
