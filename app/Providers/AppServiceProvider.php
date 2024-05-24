<?php

namespace App\Providers;

use App\Listeners\ReportEventSubscriber;
use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
    }
}
