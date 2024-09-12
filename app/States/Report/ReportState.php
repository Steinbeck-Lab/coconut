<?php

namespace App\States\Report;

use Filament\Facades\Filament;
use Maartenpaauw\Filament\ModelStates\Concerns\ProvidesSpatieStateToFilament;
use Maartenpaauw\Filament\ModelStates\Contracts\FilamentSpatieState;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ReportState extends State implements FilamentSpatieState
{
    use ProvidesSpatieStateToFilament;
    // abstract public function color(): string;

    public static function config(): StateConfig
    {
        if (Filament::getTenant()) {
            return parent::config()
                ->default(DraftState::class)
                ->allowTransition(DraftState::class, SubmittedState::class);
        } else {
            return parent::config()
                ->default(DraftState::class)
                ->allowTransition(DraftState::class, SubmittedState::class)
                ->allowTransition(SubmittedState::class, ProcessingState::class)
                ->allowTransition([SubmittedState::class, ProcessingState::class], RejectedState::class, ToRejected::class)
                ->allowTransition([SubmittedState::class, ProcessingState::class], ApprovedState::class, ToApproved::class);
        }

    }
}
