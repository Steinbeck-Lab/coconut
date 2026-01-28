<?php

namespace App\Filament\Dashboard\Resources\CollectionResource\Widgets;

use App\Models\Collection;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EntriesOverview extends BaseWidget
{
    public ?Collection $record = null;

    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        return [
            Stat::make('Entries', $this->record->total_entries)
                ->description('Total count')
                ->color('primary'),
            Stat::make('Passed Entries', $this->record->successful_entries)
                ->description('Successful count')
                ->color('success'),
            Stat::make('Entries', $this->record->failed_entries)
                ->description('Failed entries')
                ->color('danger'),
        ];
    }
}
