<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Widgets;

use App\Models\Collection;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EntriesOverview extends BaseWidget
{
    public ?Collection $record = null;

    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        return [
            Stat::make('Entries', $this->record->entries->count())
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', $this->record->entries->where('status', 'PASSED')->count())
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', $this->record->entries->where('status', 'REJECTED')->count())
                ->description('Failed entries')
                ->color('danger'),
        ];
    }
}
