<?php

namespace App\States\Report;

use Filament\Support\Contracts\HasLabel;

class SubmittedState extends ReportState implements HasLabel
{
    public static $name = 'submitted';

    public function getLabel(): string
    {
        return __('Submit');
    }

    // public function color(): string
    // {
    //     return 'green';
    // }
}
