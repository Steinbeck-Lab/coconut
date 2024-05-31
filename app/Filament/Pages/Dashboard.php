<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ApplicationOverview;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-s-cog';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Control Panel';

    public function getHeaderWidgets(): array
    {
        return [
            ApplicationOverview::class,
        ];
    }
}
