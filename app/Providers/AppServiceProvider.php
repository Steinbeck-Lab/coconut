<?php

namespace App\Providers;

use App\Listeners\ReportEventSubscriber;
use App\Models\Citation;
use App\Models\Collection;
use App\Models\GeoLocation;
use App\Models\Molecule;
use App\Models\Organism;
use App\Observers\CitationObserver;
use App\Observers\CollectionObserver;
use App\Observers\GeoLocationObserver;
use App\Observers\MoleculeObserver;
use App\Observers\OrganismObserver;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::environment('production') || App::environment('development')) {
            URL::forceScheme('https');
        }

        // Register Model Observers for event-driven cache invalidation
        Molecule::observe(MoleculeObserver::class);
        Collection::observe(CollectionObserver::class);
        Organism::observe(OrganismObserver::class);
        Citation::observe(CitationObserver::class);
        GeoLocation::observe(GeoLocationObserver::class);

        Filament::serving(function () {
            Filament::registerUserMenuItems([
                UserMenuItem::make()
                    ->label('Profile')
                    ->url('/user/profile')
                    ->icon('heroicon-s-cog'),
            ]);
        });

        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            $panelSwitch
                ->modalWidth('sm')
                ->slideOver()
                ->icons([
                    'control-panel' => 'heroicon-s-cog',
                    'dashboard' => 'heroicon-s-building-office-2',
                ])
                ->iconSize(16)
                ->labels([
                    'control-panel' => 'Control Panel',
                    'dashboard' => 'Coconut Dashboard',
                ])
                ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                    'super_admin',
                    'admin',
                    'dev',
                ]));
        });

        Event::subscribe(ReportEventSubscriber::class);

        FilamentAsset::register([
            Js::make('coconut-js', Vite::asset('resources/js/app.js'))->module(),
        ]);
    }
}
