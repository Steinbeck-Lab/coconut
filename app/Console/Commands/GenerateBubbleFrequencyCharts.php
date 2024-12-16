<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateBubbleFrequencyCharts extends Command
{
    protected $signature = 'coconut:generate-bubble-frequency-charts';

    protected $description = 'Generate bubble frequency charts data for molecular tables and columns';

    private $columnsForCharts = [
        [
            'first_column' => 'chemical_class',
            'first_table' => 'properties',
            'second_column' => 'np_classifier_class',
            'second_table' => 'properties',
        ],
    ];

    public function handle()
    {
        $bubbleFrequencyPlotData = [];

        foreach ($this->columnsForCharts as $columnsInfo) {
            $first_column = $columnsInfo['first_column'];
            $second_column = $columnsInfo['second_column'];
            $first_table = $columnsInfo['first_table'];
            $second_table = $columnsInfo['second_table'];

            $query1 = "SELECT DISTINCT f.$first_column col, COUNT(*) count FROM $first_table f  WHERE f.$first_column IS NOT NULL AND f.$first_column!='' ";
            $first_table == 'properties' ? $query1.'JOIN molecules m ON f.molecule_id=m.id WHERE m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE) ' : '';
            $query2 = "SELECT DISTINCT s.$second_column col, COUNT(*) count FROM $second_table s WHERE s.$second_column IS NOT NULL AND s.$second_column!='' ";
            $second_table == 'properties' ? $query2.'JOIN molecules m ON s.molecule_id=m.id WHERE m.active = TRUE AND NOT (m.is_parent = TRUE AND m.has_variants = TRUE) ' : '';

            $query1 .= 'GROUP BY 1';
            $query2 .= 'GROUP BY 1';

            $bubbleFrequencyPlotData[$first_column.'|'.$first_column.'|'.uniqid()] = DB::select("
                WITH
                t1 as ($query1)
                SELECT t1.col column_values,  t1.count first_column_count   from t1 order by 2 desc limit 170;
            ");

            $bubbleFrequencyPlotData[$second_column.'|'.$second_column.'|'.uniqid()] = DB::select("
            WITH
            t1 as ($query2)
            SELECT t1.col column_values,  t1.count second_column_count   from t1 order by 2 desc limit 170;
            ");

            // $bubbleFrequencyPlotData[$first_column.'|'.$second_column.'|'.uniqid()] = DB::select("
            //     WITH
            //     t1 as ($query1),
            //     t2 as ($query2)
            //     SELECT COALESCE(t1.col, t2.col) column_values , t1.count first_column_count, t2.count second_column_count FROM t1 LEFT JOIN t2 ON t1.col=t2.col WHERE t2.col IS NULL
            //     ;
            // ");

            // $bubbleFrequencyPlotData[$first_column.'|'.$second_column.'|'.uniqid()] = DB::select("
            //     WITH
            //     t1 as ($query1),
            //     t2 as ($query2)
            //     SELECT COALESCE(t1.col, t2.col) column_values , t1.count first_column_count, t2.count second_column_count FROM t1 RIGHT JOIN t2 ON t1.col=t2.col WHERE t1.col IS NULL
            //     ;
            // ");

        }

        $filePath = public_path('reports/bubble_frequency_charts.json');
        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        File::put($filePath, json_encode($bubbleFrequencyPlotData, JSON_UNESCAPED_SLASHES));

        $this->info('Bubble Frequency charts data saved to: public/reports/bubble_frequency_charts.json');

    }
}
