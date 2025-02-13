<?php

namespace App\Providers;

use App\Http\Requests\CustomSearchRequest;
use App\Listeners\ReportEventSubscriber;
use App\Rest\Query\CustomBuilder;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Lomkit\Rest\Contracts\QueryBuilder;
use Lomkit\Rest\Http\Requests\SearchRequest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving(SearchRequest::class, function () {
            $this->app->singleton(SearchRequest::class, CustomSearchRequest::class);
        });
        $this->app->bind(QueryBuilder::class, CustomBuilder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') === 'production' || env('APP_ENV') === 'development') {
            URL::forceScheme('https');
        }

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
