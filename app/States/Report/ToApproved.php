<?php

namespace App\States\Report;

use App\Models\Report;
use Filament\Forms\Components\Textarea;
use Maartenpaauw\Filament\ModelStates\Concerns\ProvidesSpatieTransitionToFilament;
use Maartenpaauw\Filament\ModelStates\Contracts\FilamentSpatieTransition;
use Spatie\ModelStates\Transition;

final class ToApproved extends Transition implements FilamentSpatieTransition
{
    use ProvidesSpatieTransitionToFilament;

    public function __construct(
        private readonly Report $report,
        private readonly string $reason = '',
    ) {}

    public function handle(): Report
    {
        $this->report->state = new ApprovedState($this->report);
        $this->report->comments = $this->reason;

        $this->report->save();

        return $this->report;
    }

    public function form(): array
    {
        return [
            Textarea::make('comments')
                ->required()
                ->minLength(1)
                ->maxLength(1000)
                ->rows(5)
                ->helperText(__('This reason will be sent to the report creator.')),
        ];
    }
}
