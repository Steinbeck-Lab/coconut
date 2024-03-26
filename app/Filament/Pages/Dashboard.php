<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApplicationOverview;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            ApplicationOverview::class,
        ];
    }
}
