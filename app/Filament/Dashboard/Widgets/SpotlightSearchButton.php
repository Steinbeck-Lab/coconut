<?php

namespace App\Filament\Dashboard\Widgets;

use Filament\Widgets\Widget;

class SpotlightSearchButton extends Widget
{
    protected string $view = 'filament.dashboard.widgets.spotlight-search-button';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }
}
